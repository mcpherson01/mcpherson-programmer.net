<?php
/**
 * Plugin Name: WP Enable WebP
 * Plugin URI: https://eastsidecode.com
 * Description: WordPress plugin to enable WebP image uploads.
 * Version: 1.0
 * Author: Louis Fico
 * Author URI: http://eastsidecode.com
 * License: GPL12
 */

if ( ! defined( 'ABSPATH' ) ) exit;


function webpSupported() {
    if( strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false ) {
        return true;
    } else {
        return false;
    }
}



function webPFormatChecker($classes) {
   if( strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false ) {
        // webp is supported
        $classes[] = 'supports-webp';
    } else {
        // webp is not supported
        $classes[] = 'no-webp-support';
    }
    return $classes;
}

add_filter('body_class', 'webPFormatChecker');




add_filter( 'wp_check_filetype_and_ext', 'wpse_file_and_ext_webp', 10, 4 );
function wpse_file_and_ext_webp( $types, $file, $filename, $mimes ) {
    if ( false !== strpos( $filename, '.webp' ) ) {
        $types['ext'] = 'webp';
        $types['type'] = 'image/webp';
    }

    return $types;
}


add_filter( 'upload_mimes', 'wpse_mime_types_webp' );
function wpse_mime_types_webp( $mimes ) {
    $mimes['webp'] = 'image/webp';

  return $mimes;
}