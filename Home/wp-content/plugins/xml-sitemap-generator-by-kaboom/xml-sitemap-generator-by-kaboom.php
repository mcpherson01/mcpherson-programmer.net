<?php

/**
 * @package XML Sitemap Generator by Kaboom
 */
/*
Plugin Name: Kaboom XML/HTML Sitemap Generator 
Description: A plugin that generate a XML/HTML sitemap for your WordPress site. HTML sitemap: [kaboom-sitemap] for your XML sitemap visit /sitemap.xml
Version: 2.3.17
Author: Kaboom
Author URI: https://kaboom.website
License: GPLv2 or later
Text Domain: XML Sitemap Generator by Kaboom
*/

/*
XML Sitemap Generator by Kaboom is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or
any later version.

XML Sitemap Generator by Kaboom is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with XML Sitemap Generator by Kaboom.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class XMLSitemapGeneratorByKaboom
{
    
    function __construct(){
        add_action('admin_menu', array( $this, 'add_to_menu' ));
        add_action('init', array( $this, 'init'));
        add_action('admin_notices', array( $this, 'leave_a_review' ), 20);
        add_action('admin_init', array( $this, 'leave_a_review_dismissed' ));
        add_action('admin_init', array( $this, 'admin_interface'));
        register_activation_hook(__FILE__, array( $this, 'install' ));
        add_shortcode( 'kaboom-sitemap', array( $this,  'kaboom_sitemap'));
    }
    
    function install(){
        update_option('sitemap_generator_exclude_post_types', 'post,page');
    }
    function add_to_menu(){

        if (get_option('form_email_catcher_by_kaboom_activated') == '0'){
          $name = 'Email Catcher <span class="awaiting-mod">1</span>';
        } else { 
          $name = 'Email Catcher';
        }
        global $admin_page_hooks;
        if ( empty ( $GLOBALS['admin_page_hooks']['kaboom'] ) ){
            add_menu_page(
                'Kaboom', 
                'Kaboom', 
                'manage_options', 
                'kaboom', 
                array( $this,  'kaboom_main' ), 
                plugins_url('/images/blue-dot.svg', __FILE__)
            );
        }
        add_submenu_page(
            'kaboom', 
            'Sitemap Generator', 
            'Sitemap', 
            'manage_options', 
            'XML Sitemap Generator By Kaboom', 
            array( $this,  'settings' )
        );
    }

    function admin_interface(){
        add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'kaboom_sitemap_link' );
        wp_enqueue_script( 'kaboom_script', plugins_url( '/view/admin.js' , __FILE__ ) );

        function kaboom_sitemap_link( $links ) {
           $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=XML+Sitemap+Generator+By+Kaboom') ) .'">Settings</a>';
           return $links;
        }

        if (isset($_GET['update_exclude_ids']) && current_user_can('manage_options')){
          $exclude_ids = sanitize_text_field($_GET['ids']);
          update_option( 'sitemap_generator_exclude_ids', $exclude_ids);        }
    }


    function leave_a_review() {
        $user_id = get_current_user_id();
        if ( !get_user_meta( $user_id, 'leave_a_review_sitemap_kaboom_' ) && isset($_GET["leave_a_review_dismissed"]) == false){
          if (isset($_GET['page']) && $_GET['page'] == 'XML Sitemap Generator By Kaboom'){
          } else {
            echo '<div class="notice notice-success leave-a-review"><p><a href="/wp-admin/admin.php?page=XML+Sitemap+Generator+By+Kaboom"><img style="float:left;width:58px;margin-right:20px;" src="https://app.kaboom.website/images/sitemap-generator-logo.svg"></a>Do you like the XML/HTML Sitemap Generator?</p><p><a href="https://wordpress.org/support/plugin/xml-sitemap-generator-by-kaboom/reviews/#new-post" target="_blank" class="button" style="background:#46b450;color:white"><span style="padding-top:4px;" class="dashicons dashicons-yes"></span>Write a review</a>  <a class="button"  style="background:#dc3545;color:white" href="/wp-admin/index.php?leave_a_review_dismissed"><span style="padding-top:4px;" class="dashicons dashicons-no"></span>Dismiss</a></p></div>';
            }
          }
    }

    function leave_a_review_dismissed() {
        $user_id = get_current_user_id();
        if ( isset( $_GET['leave_a_review_dismissed'] ) ){
            add_user_meta( $user_id, 'leave_a_review_sitemap_kaboom_', 'true', true );
            echo '<div class="notice notice-warning"><p>Message dismissed for the XML/HTML Sitemap Generator by Kaboom plugin!</p></div>';
        }
    }
    
    //[kaboom-sitemap]
    function kaboom_sitemap( $atts ){
        if (get_option( 'sitemap_generator_by_kaboom_license')){
          $url = 'https://app.kaboom.website/test_token/' . get_option('sitemap_generator_by_kaboom_license') . '?host=' . get_site_url() . '/kaboom-sitemap-shortcode';
          $api_valid = file($url);
        } else {
          $url='https://app.kaboom.website/test_token/free?host=' . get_site_url()  . '/kaboom-sitemap-shortcode';
          $api_valid = file($url);
        }            
        $post_types_final = explode(',', get_option('sitemap_generator_exclude_post_types'));
        $ids              = explode(',', get_option('sitemap_generator_exclude_ids'));
        $query = new WP_Query( array(
            'post_type' => $post_types_final,
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'suppress_filters' => 'false',
            'post__not_in' => $ids,
            'post_parent' => 0
        ) );
        $html = '<ul class="xml-sitemap-generator-by-kaboom">';
        while ($query->have_posts()):
            $query->the_post();
            global $post;
            $html = $html . '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';

            // First child
            $args = array(
                'post_type' => $post_types_final,
                'posts_per_page' => -1,
                'post_status' => 'publish',                
                'post_parent'    => $post->ID,
                'post__not_in' => $ids,
             );
            $children = new WP_Query($args);
            
            if ($children->found_posts > 0){
              $html = $html . '<ul class="sub" style="padding-bottom:0">';
            }
            while ($children->have_posts()):
                $children->the_post();
                global $post;
                
                // Second child
                $args = array(
                    'post_type' => $post_types_final,
                    'posts_per_page' => -1,
                    'post_status' => 'publish',                
                    'post_parent'    => $post->ID,
                    'post__not_in' => $ids,
                 );
                $sub_children = new WP_Query($args);
                $html = $html . '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
                
                if ($sub_children->found_posts > 0){
                  $html = $html . '<ul class="sub " style="padding-bottom:0">';
                }
                
                while ($sub_children->have_posts()):
                    $sub_children->the_post();
                    global $post;
                    
                    // Third child
                    $args = array(
                        'post_type' => $post_types_final,
                        'posts_per_page' => -1,
                        'post_status' => 'publish',                
                        'post_parent'    => $post->ID,
                        'post__not_in' => $ids,
                     );
                    $sub_sub_children = new WP_Query($args);
                    $html = $html . '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
                    
                    if ($sub_sub_children->found_posts > 0){
                      $html = $html . '<ul class="sub_sub" style="padding-bottom:0">';
                    }
                    
                    while ($sub_sub_children->have_posts()):
                        $sub_sub_children->the_post();
                        global $post;
                        $html = $html . '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';

                    endwhile;
                    if ($sub_sub_children->found_posts > 0){
                      $html = $html . '</ul>';
                    }

                endwhile;
                if ($sub_children->found_posts > 0){
                  $html = $html . '</ul>';
                }

            endwhile;
            if ($children->found_posts > 0){
              $html = $html . '</ul>';
            }
        endwhile;
        $html = $html . '</ul>';
        return $html;
    }

    function init()
    {

        if ($_SERVER['REQUEST_URI'] == '/sitemap.xml') {

            if (get_option( 'sitemap_generator_by_kaboom_license')){
              $url = 'https://app.kaboom.website/test_token/' . get_option('sitemap_generator_by_kaboom_license') . '?host=' . get_site_url() . '/sitemap.xml';
              $api_valid = file($url);
            } else {
              $url='https://app.kaboom.website/test_token/free?host=' . get_site_url()  . '/sitemap.xml';
              $api_valid = file($url);
            }            
            $is_pro = isset($api_valid) && implode('', $api_valid) == 'Your API key is valid';
            $post_types_final = explode(',', get_option('sitemap_generator_exclude_post_types'));
            $ids              = explode(',', get_option('sitemap_generator_exclude_ids'));
            $query = new WP_Query( array(
                'post_type' => $post_types_final,
                'posts_per_page' => $is_pro ? '-1' : '250',
                'post_status' => 'publish',
                'suppress_filters' => 'false',
                'post__not_in' => $ids
            ) );
            header('Content-type: text/xml');
            header('Pragma: public');
            header('Cache-control: private');
            header('Expires: -1');
            
            ob_end_clean();
            echo "<?xml version=\"1.0\" encoding=\"utf-8\"?><!-- Generated by XML Sitemap Generator by Kaboom -->\n";
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
            echo "\n";

            while ($query->have_posts()):
                $query->the_post();
                global $post;
                echo "  <url>\n";
                global $sitepress;
                if ($sitepress) {
                    $lang = wpml_get_language_information($post->ID);
                    $sitepress->switch_lang($lang['language_code'], true);
                    echo '    <loc>';
                    echo get_permalink();
                    echo "</loc>\n";
                    
                    if ($is_pro ){
                      $post_trid = apply_filters('wpml_element_trid', NULL, $post->ID, 'post_' . get_post_type());
                      if (!empty($post_trid)) {
                          $translations = apply_filters('wpml_get_element_translations', NULL, $post_trid, 'post_' . get_post_type());
                          
                          if (is_array($translations) && !empty($translations)) {
                              
                              if (count($translations) > 1) {
                                  foreach ($translations as $translation) {
                                      if (get_post($translation->element_id)->post_status == 'publish'){
                                          $sitepress->switch_lang($translation->language_code, true);                                
                                          echo '    <xhtml:link rel="alternate" hreflang="' . $translation->language_code . '" href="' . get_permalink($translation->element_id) . '"></xhtml:link>';
                                          echo "\n";
                                      }
                                  }
                              }
                          }
                      }
                    }
                } else {
                    echo '    <loc>';
                    echo get_permalink();
                    echo "  </loc>\n";
                }
                echo "  </url>\n";
            endwhile;
            echo '</urlset>';
            exit;        
        }
    }
    
    function settings()
    {
        wp_register_style('kaboom-styling', plugins_url('/view/style.css', __FILE__));
        wp_enqueue_style('kaboom-styling');
        
        include(dirname(__FILE__) . "/view/settings.php");
    }

    function kaboom_main()
    {
        wp_register_style('kaboom-styling', plugins_url('/view/style.css', __FILE__));
        wp_enqueue_style('kaboom-styling');
        
        include(dirname(__FILE__) . "/view/kaboom.php");
    }    
}

new XMLSitemapGeneratorByKaboom();

function xml_html_sitemap_add_custom_box()
{
    add_meta_box(
        'xml_html_sitemap_box_id',           // Unique ID
        '<img style="float:left;width:20px;margin-right:4px;" src="https://app.kaboom.website/images/blue-dot.svg">XML/HTML sitemap generator Settings',  // Box title
        'xml_html_sitemap_custom_box_html'
    );
}
add_action('add_meta_boxes', 'xml_html_sitemap_add_custom_box');

function xml_html_sitemap_custom_box_html($post)
{
    $orr_arr = explode(',', get_option('sitemap_generator_exclude_ids'));
    $arr = $orr_arr;
    $enabled = array_diff($arr, array($post->ID));
    if (!in_array($post->ID, $arr)){
      array_push($arr, $post->ID);
    }
    $disable = $arr;
    ?>
    <p><label for="exclude_ids">Exclude or include in sitemap</label></p>
    <div class="kaboom-alert"></div>
    <select name="exclude_ids" id="exclude_ids" class="postbox async">
        <option message="Successfully enabled this post" <?php if ($orr_arr == $enabled) {echo 'selected';} ?> value="<?php echo( implode(',', $enabled) ) ?>">✅ Include</option>
        <option message="Successfully disabled this post" <?php if ($orr_arr == $disable) {echo 'selected';} ?> value="<?php echo( implode(',', $disable) ) ?>">❌ Exclude</option>
    </select>
    <?php
}
?>