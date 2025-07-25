<?php

defined('ABSPATH') || exit;

function ecf_admin_init() {
    add_action('admin_menu', 'ecf_add_admin_menu');
    add_action('admin_init', 'ecf_settings_init');
    add_action('admin_post_ecf_force_refresh', 'ecf_handle_force_refresh');
    add_action('admin_post_ecf_generate_test_post', 'ecf_handle_generate_test_post');
    add_action('admin_post_ecf_generate_all_posts', 'ecf_handle_generate_all_posts');
    add_action('admin_init', 'ecf_clean_notifications');
}

function ecf_clean_notifications() {
    // Clean up stacked notifications on our settings page
    if (isset($_GET['page']) && $_GET['page'] === 'country-fields') {
        $current_url = $_SERVER['REQUEST_URI'];
        $clean_params = array('settings-updated', 'error', 'refreshed', 'test_created', 'test_url', 'test_title', 'jobs_queued');
        $has_multiple = 0;
        
        foreach ($clean_params as $param) {
            if (isset($_GET[$param])) {
                $has_multiple++;
            }
        }
        
        // If multiple notification params exist, keep only the most recent one
        if ($has_multiple > 1) {
            $redirect_url = admin_url('options-general.php?page=country-fields');
            
            // Priority: error > test_created > jobs_queued > refreshed > settings-updated
            if (isset($_GET['error'])) {
                $redirect_url .= '&error=' . $_GET['error'];
            } elseif (isset($_GET['test_created'])) {
                $redirect_url .= '&test_created=' . $_GET['test_created'] . '&test_url=' . $_GET['test_url'] . '&test_title=' . $_GET['test_title'];
            } elseif (isset($_GET['jobs_queued'])) {
                $redirect_url .= '&jobs_queued=' . $_GET['jobs_queued'];
            } elseif (isset($_GET['refreshed'])) {
                $redirect_url .= '&refreshed=1';
            } elseif (isset($_GET['settings-updated'])) {
                $redirect_url .= '&settings-updated=true';
            }
            
            wp_redirect($redirect_url);
            exit;
        }
    }
}

function ecf_add_admin_menu() {
    add_options_page(
        'Country Fields Settings',
        'Country Fields',
        'manage_options',
        'country-fields',
        'ecf_options_page'
    );
}

function ecf_settings_init() {
    register_setting('ecf_settings', 'ecf_google_sheets_url');
    
    add_settings_section(
        'ecf_settings_section',
        'Google Sheets Configuration',
        'ecf_settings_section_callback',
        'ecf_settings'
    );
    
    add_settings_field(
        'ecf_google_sheets_url',
        'Google Sheets CSV URL',
        'ecf_google_sheets_url_render',
        'ecf_settings',
        'ecf_settings_section'
    );
}

function ecf_settings_section_callback() {
    echo '<p>Configure the Google Sheets CSV URL for country data.</p>';
}

function ecf_google_sheets_url_render() {
    $url = get_option('ecf_google_sheets_url', '');
    echo '<input type="url" name="ecf_google_sheets_url" value="' . esc_attr($url) . '" class="regular-text" placeholder="https://..." />';
    echo '<p class="description">Enter the public CSV export URL from your Google Sheets document.</p>';
}

function ecf_handle_force_refresh() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    if (!wp_verify_nonce($_POST['_wpnonce'], 'ecf_force_refresh')) {
        wp_die('Security check failed');
    }
    
    // Clear cache first
    delete_transient('country_data_cache');
    delete_option('ecf_cache_last_updated');
    
    // Immediately fetch fresh data
    $fresh_data = fetch_country_data();
    
    if ($fresh_data === false) {
        wp_redirect(add_query_arg(array(
            'page' => 'country-fields',
            'error' => '1'
        ), remove_query_arg(array('settings-updated', 'refreshed'), admin_url('options-general.php'))));
    } else {
        // Verify cache was actually set
        $verify_cache = get_transient('country_data_cache');
        if ($verify_cache === false) {
            wp_redirect(add_query_arg(array(
                'page' => 'country-fields',
                'error' => '2'
            ), remove_query_arg(array('settings-updated', 'refreshed'), admin_url('options-general.php'))));
        } else {
            // Remove any existing query params to clear previous messages
            wp_redirect(add_query_arg(array(
                'page' => 'country-fields',
                'refreshed' => '1'
            ), remove_query_arg(array('settings-updated', 'error'), admin_url('options-general.php'))));
        }
    }
    exit;
}

function ecf_options_page() {
    ?>
    <div class="wrap">
        <h1>Country Fields Settings <small style="font-size: 0.6em; color: #666; font-weight: normal;">v<?php echo get_plugin_data(ECF_PLUGIN_DIR . 'edible-country-fields.php')['Version']; ?></small></h1>
        
        <?php if (isset($_GET['refreshed'])): ?>
            <div class="notice notice-success is-dismissible">
                <p>Cache refreshed and data refetched successfully!</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="notice notice-error is-dismissible">
                <?php if ($_GET['error'] == '1'): ?>
                    <p>Failed to fetch fresh data. Please check your Google Sheets URL and try again.</p>
                <?php elseif ($_GET['error'] == '2'): ?>
                    <p>Data was fetched but failed to cache properly. Please try again.</p>
                <?php elseif ($_GET['error'] == 'no_active'): ?>
                    <p>No active countries found in CSV data. Please check your data and ensure the 'active' column contains 'true' values.</p>
                <?php elseif ($_GET['error'] == 'test_failed'): ?>
                    <p>Failed to create test post. Please check your data and try again.</p>
                <?php else: ?>
                    <p>An error occurred. Please try again.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['test_created'])): ?>
            <div class="notice notice-success is-dismissible">
                <p>Test post created successfully! <a href="<?php echo esc_url(urldecode($_GET['test_url'])); ?>" target="_blank"><?php echo esc_html(urldecode($_GET['test_title'])); ?></a></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['jobs_queued'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo intval($_GET['jobs_queued']); ?> jobs queued successfully for country post generation.</p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['settings-updated'])): ?>
            <div class="notice notice-success is-dismissible">
                <p>Settings saved!</p>
            </div>
        <?php endif; ?>
        
        <!-- Settings and Management Section -->
        <div class="metabox-holder columns-2">
            <div class="postbox-container" style="width: 68%; margin-right: 2%;">
                
                <!-- Configuration Section -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Configuration</h2>
                    </div>
                    <div class="inside">
                        <form action="options.php" method="post">
                            <?php
                            settings_fields('ecf_settings');
                            do_settings_sections('ecf_settings');
                            submit_button('Save Settings', 'primary', 'submit', false);
                            ?>
                        </form>
                    </div>
                </div>
                
            </div>
            
            <div class="postbox-container" style="width: 30%;">
                
                <!-- Cache Management Sidebar -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Cache Management</h2>
                    </div>
                    <div class="inside">
                        <p>Refresh the country data from Google Sheets. This will clear the current cache and fetch fresh data.</p>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                            <input type="hidden" name="action" value="ecf_force_refresh">
                            <?php wp_nonce_field('ecf_force_refresh'); ?>
                            <?php submit_button('Refresh Cache', 'secondary', 'refresh', false, array('style' => 'width: 100%;')); ?>
                        </form>
                        
                        <?php
                        $cache_data = get_transient('country_data_cache');
                        if ($cache_data !== false) {
                            $country_count = count($cache_data);
                            echo '<hr>';
                            echo '<p><strong>Cache Status:</strong> Active</p>';
                            echo '<p><strong>Countries:</strong> ' . $country_count . '</p>';
                            
                            // Show last update time
                            $last_update = get_option('ecf_cache_last_updated', '');
                            if ($last_update) {
                                $formatted_time = wp_date(get_option('date_format') . ' ' . get_option('time_format'), $last_update);
                                echo '<p><strong>Last Updated:</strong> ' . $formatted_time . '</p>';
                            }
                            
                            echo '<p><strong>Cache Expires:</strong> Manual refresh only</p>';
                        } else {
                            echo '<hr>';
                            echo '<p><strong>Cache Status:</strong> Empty</p>';
                            echo '<p><em>Data will be fetched on first shortcode use.</em></p>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Country Posts Generation -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Country Posts</h2>
                    </div>
                    <div class="inside">
                        <p>Generate WordPress posts for countries marked as active in your CSV data.</p>
                        
                        <div style="margin-bottom: 15px;">
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                <input type="hidden" name="action" value="ecf_generate_test_post">
                                <?php wp_nonce_field('ecf_generate_test_post'); ?>
                                <?php submit_button('Generate Test Post', 'secondary', 'test_post', false, array('style' => 'margin-right: 10px;')); ?>
                            </form>
                            <small>Creates one random active country post for testing.</small>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                <input type="hidden" name="action" value="ecf_generate_all_posts">
                                <?php wp_nonce_field('ecf_generate_all_posts'); ?>
                                <?php submit_button('Generate All Posts', 'primary', 'all_posts', false, array('style' => 'margin-right: 10px;')); ?>
                            </form>
                            <small>Queues jobs to create posts for all active countries.</small>
                        </div>
                        
                        <div id="ecf-job-progress" style="display: none;">
                            <hr>
                            <p><strong>Job Status:</strong></p>
                            <div class="ecf-progress-bar" style="background: #f1f1f1; border-radius: 3px; padding: 3px;">
                                <div class="ecf-progress-fill" style="background: #0073aa; height: 20px; border-radius: 3px; width: 0%; transition: width 0.3s;"></div>
                            </div>
                            <p class="ecf-progress-text">Preparing...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Shortcode Reference -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Available Shortcodes</h2>
                    </div>
                    <div class="inside">
                        <?php
                        $shortcode_mappings = get_shortcode_mappings();
                        $special_shortcodes = array('country_area_codes_table', 'country_neighbors_list');
                        $basic_shortcodes = array_diff(array_keys($shortcode_mappings), $special_shortcodes);
                        ?>
                        
                        <p><strong>Basic Usage:</strong></p>
                        <ul style="margin-left: 20px;">
                            <?php foreach ($basic_shortcodes as $shortcode): ?>
                                <li><code>[<?php echo $shortcode; ?>]</code></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <p><strong>Special Formatting:</strong></p>
                        <ul style="margin-left: 20px;">
                            <?php foreach ($special_shortcodes as $shortcode): ?>
                                <li><code>[<?php echo $shortcode; ?>]</code></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <p><em>Use these shortcodes in posts or pages. The plugin uses the post slug to determine which country data to display.</em></p>
                    </div>
                </div>
                
            </div>
        </div>
        
    </div>
    <?php
}

function ecf_handle_generate_test_post() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    if (!wp_verify_nonce($_POST['_wpnonce'], 'ecf_generate_test_post')) {
        wp_die('Security check failed');
    }
    
    // Get active countries from CSV
    $active_countries = ecf_get_active_countries();
    
    if (empty($active_countries)) {
        wp_redirect(add_query_arg(array(
            'page' => 'country-fields',
            'error' => 'no_active'
        ), admin_url('options-general.php')));
        exit;
    }
    
    // Pick random active country
    $random_slug = array_rand($active_countries);
    $random_country = array($random_slug => $active_countries[$random_slug]);
    
    // Create the post
    $post_id = ecf_create_country_post($random_country);
    
    if ($post_id) {
        $post_url = get_permalink($post_id);
        $post_title = get_the_title($post_id);
        wp_redirect(add_query_arg(array(
            'page' => 'country-fields',
            'test_created' => $post_id,
            'test_url' => urlencode($post_url),
            'test_title' => urlencode($post_title)
        ), admin_url('options-general.php')));
    } else {
        wp_redirect(add_query_arg(array(
            'page' => 'country-fields',
            'error' => 'test_failed'
        ), admin_url('options-general.php')));
    }
    exit;
}

function ecf_handle_generate_all_posts() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    if (!wp_verify_nonce($_POST['_wpnonce'], 'ecf_generate_all_posts')) {
        wp_die('Security check failed');
    }
    
    // Queue jobs for all active countries
    $queued_count = ecf_queue_all_country_posts();
    
    wp_redirect(add_query_arg(array(
        'page' => 'country-fields',
        'jobs_queued' => $queued_count
    ), admin_url('options-general.php')));
    exit;
}


?>