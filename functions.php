<?php

// =============================================================================
// FUNCTIONS.PHP
// -----------------------------------------------------------------------------
// Overwrite or add your own custom functions to X in this file.
// =============================================================================

// =============================================================================
// TABLE OF CONTENTS
// -----------------------------------------------------------------------------
//   01. Enqueue Parent Stylesheet
//   02. Additional Functions
// =============================================================================

// Enqueue Parent Stylesheet
// =============================================================================

add_filter( 'x_enqueue_parent_stylesheet', '__return_true' );



// Customize WooCommerce
// =============================================================================

//Add sidebar to product pages


// if ( ! function_exists( 'x_get_content_layout' ) ) :
//   function x_get_content_layout() {

//     $stack          = x_get_stack();
//     $content_layout = x_get_option( 'x_' . $stack . '_layout_content', 'content-sidebar' );

//     if ( $content_layout != 'full-width' ) {
//       if ( is_home() ) {
//         $opt    = x_get_option( 'x_blog_layout', 'sidebar' );
//         $layout = ( $opt == 'sidebar' ) ? $content_layout : $opt;
//       } elseif ( is_singular( 'post' ) ) {
//         $meta   = get_post_meta( get_the_ID(), '_x_post_layout', true );
//         $layout = ( $meta == 'on' ) ? 'full-width' : $content_layout;
//       } elseif ( x_is_portfolio_item() ) {
//         $layout = 'full-width';
//       } elseif ( x_is_portfolio() ) {
//         $meta   = get_post_meta( get_the_ID(), '_x_portfolio_layout', true );
//         $layout = ( $meta == 'sidebar' ) ? $content_layout : $meta;
//       } elseif ( is_page_template( 'template-layout-content-sidebar.php' ) ) {
//         $layout = 'content-sidebar';
//       } elseif ( is_page_template( 'template-layout-sidebar-content.php' ) ) {
//         $layout = 'sidebar-content';
//       } elseif ( is_page_template( 'template-layout-full-width.php' ) ) {
//         $layout = 'full-width';
//       } elseif ( is_archive() ) {
//         if ( x_is_shop() || x_is_product_category() || x_is_product_tag() || x_is_product() ) {
//           $opt    = x_get_option( 'x_woocommerce_shop_layout_content', 'sidebar' );
//           $layout = ( $opt == 'sidebar' ) ? $content_layout : $opt;
//         } else {
//           $opt    = x_get_option( 'x_archive_layout', 'sidebar' );
//           $layout = ( $opt == 'sidebar' ) ? $content_layout : $opt;
//         }
//       } elseif ( x_is_bbpress() ) {
//         $opt    = x_get_option( 'x_bbpress_layout_content', 'sidebar' );
//         $layout = ( $opt == 'sidebar' ) ? $content_layout : $opt;
//       } elseif ( x_is_buddypress() ) {
//         $opt    = x_get_option( 'x_buddypress_layout_content', 'sidebar' );
//         $layout = ( $opt == 'sidebar' ) ? $content_layout : $opt;
//       } elseif ( is_404() ) {
//         $layout = 'full-width';
//       } else {
//         $layout = $content_layout;
//       }
//     } else {
//       $layout = $content_layout;
//     }

//     return $layout;

//   }
// endif;

//add_filter( 'ups_sidebar', 'product_sidebar', 9999 );

//function product_sidebar ( $default_sidebar ) {
//if ( x_is_product() ) return 'ups-sidebar-adult-program'; //Must match the ID of your target sidebar
//return $default_sidebar;
//}


// Additional Functions
// =============================================================================

// Disable adminbar for non administrators
add_action('after_setup_theme', 'remove_admin_bar');

function remove_admin_bar() {
if (!current_user_can('administrator') && !is_admin()) {
  show_admin_bar(false);
}
}

// =============================================================================



/**
    * Define a version number
    * This is optional but useful to circumvent caching issues
    * 
    * There are more creative ways to get the version
    * eg: if you get the version of the current theme and use that
    * you'll never have to update the enque stuff manually
    * so long as you update your theme version (but the version
    * might be wrong if you're using a js or css library)
    * 
    */
   
  $ver = '1.1.1';


  /**
    * Define the base url for the file
    * In the 'active example below, it's assumed the files are in
    * the child-theme folder
    *
    * Other examples:
    * 
    * $base_url = get_template_directory_uri();
    * If files are in the theme folder
    *
    * $base_url = plugin_dir_url( __FILE__ );
    * If you're loading the files in a plguin
    * I dont recommend you mess with plugin folders unless
    * it's one you built yourself
    * 
    */
  $base_url = get_stylesheet_directory_uri(); // 



  /**
   * Enqueue and register CSS files here.
   * more at https://codex.wordpress.org/Function_Reference/wp_enqueue_style
   */
  $css_dependancies = array();
  function register_admin_styles() {
      wp_enqueue_style( 'style', $base_url . '/wp-content/themes/x-child/admin-style.css', $css_dependancies, $ver );
  }


  /**
    * Make sure to spit it out!
    */
  add_action( 'admin_enqueue_scripts', 'register_admin_styles' );