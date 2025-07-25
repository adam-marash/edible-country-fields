<?php

defined('ABSPATH') || exit;

// Add a filter to modify the title of the 'country' post type
// Modifies titles only when viewing single country posts
add_filter( 'the_title', 'custom_modify_post_title', 10, 2 );
add_filter( 'rank_math/frontend/title', 'custom_modify_post_title', 10, 1 );

function custom_modify_post_title( $title, $post_id = null ) {
   if ( !is_single() ) {
       return $title;
   }
   
   if ( null === $post_id ) {
       $post_id = get_the_ID();
   }
   
   if ( get_post_type($post_id) === 'country' ) {
       $title = "Send A Fax Online To $title";
   }

   return $title;
}