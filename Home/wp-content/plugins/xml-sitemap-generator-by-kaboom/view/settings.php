<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!empty($_POST) && current_user_can('manage_options')){
  
  $api_key = sanitize_text_field($_POST['api_key']);
  $exclude_ids = sanitize_text_field($_POST['exclude_ids']);
  $exclude_post_types = sanitize_text_field($_POST['exclude_post_types']);

  if(isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'],'kaboom-save-sitemap-nonce')){
    update_option( 'sitemap_generator_by_kaboom_license',  $api_key);
    update_option( 'sitemap_generator_exclude_ids', $exclude_ids);
    update_option( 'sitemap_generator_exclude_post_types', $exclude_post_types);
  } else {
    wp_add_inline_script('kaboom_script', "alert('Something went wrong, try reloading the page')");
  }
}

wp_enqueue_style('font-awesome', 'https://use.fontawesome.com/releases/v5.8.1/css/all.css');
?>
<div class="kaboom-header">
  <img src="https://app.kaboom.website/images/sitemap-generator-logo.svg" style="width:64px;float:left;margin-right:20px">
  <h1>XML Sitemap Generator by Kaboom</h1>
  <div class="alert alert-notice hidden"></div>
  <?php if (empty($_SERVER['HTTPS'])) {
    echo "<div class='alert alert-danger'>You don't use a SSL connection, please make sure you encrypt your connection before using this plugin. Your data or API key can get stolen!</div>";
  }?>
</div>
<div class="wrap">
<?php
  $path = get_home_path();
  $file = $path . 'sitemap.xml';
  if (file_exists($file)){
    unlink($file);
  }

if (get_option( 'sitemap_generator_by_kaboom_license')){
  $url='https://app.kaboom.website/test_token/' . get_option( 'sitemap_generator_by_kaboom_license')  . '?host=' . get_site_url();
  $api_valid = file($url);
} else {
  $url='https://app.kaboom.website/test_token/free?host=' . get_site_url();
  $api_valid = file($url);
}
$is_pro = isset($api_valid) && implode('',$api_valid) == 'Your API key is valid';
?>
  <div class="card left">
    <div class="card-body">
          
      <div class="" id="control_center">           
        <p class="title">Settings
          <a href="/sitemap.xml" class="button button-primary" style="float:right" target="_blank">Bekijk sitemap.xml</a></p> 
        <hr>
        <form method="post" class="submit-for-exclude-ids" id="submit-for-exclude-ids" autocomplete="off">
        <?php
          wp_nonce_field('kaboom-save-sitemap-nonce');
        ?>
          <div class="row">
            <div class="col-6">
              <div class="form-group">
                <label>
                  Exclude post types
                </label>
                <div>
                <?php  
                  $post_types = [];
                  foreach ( get_post_types( '', 'names' ) as $post_type ) {
                    global $wp_post_types;
                    $obj = $wp_post_types[ $post_type ];
                    if (!$obj->exclude_from_search == 1){
                      echo '<p class="post-type-activation disabled" post-type="' . $post_type . '">' . $obj->labels->singular_name . '</p>';
                    }
                  }
                ?>
              </div>
                <input type="text" name="exclude_post_types" class="exclude_post_types" value="<?php echo esc_attr(get_option( 'sitemap_generator_exclude_post_types' )); ?>">
              </div> 
            </div>
            <div class="col-6">  
              <div class="form-group">
                <label>
                  Exclude pages
                </label>
                <input type="text" class="exclude form-control" readonly>
                <input type="text" name="exclude_ids" class="form-control old-ids" value="<?php echo esc_attr(get_option( 'sitemap_generator_exclude_ids' )); ?>" autocomplete="false">
                <div class="text-right" style="padding-top:10px">
                  <button class="button button-primary safe-exclude" type="submit">Safe excluded pages</button>
                  <button class="button button-danger remove-exclude">Remove excluded pages</button>            
                </div>
              </div>
            </div>   
          </div>      
      <?php
          $post_types_final = explode(',',get_option( 'sitemap_generator_exclude_post_types' )); 
          $ids              = explode(',', get_option('sitemap_generator_exclude_ids'));
          
          $disabled = new WP_Query( 
            array(
              'post_type' => $post_types_final, 
              'posts_per_page' => $is_pro ? '-1' : '250', 
              'post_status' => 'publish', 
              'suppress_filters' => 'false', 
              'orderby' => 'post_name',
               'order' => 'ASC',
              'post__in' => $ids
            ) );
          $enabled = new WP_Query( 
            array(
              'post_type' => $post_types_final, 
              'posts_per_page' => $is_pro ? '-1' : (250 - (int)$disabled->post_count), 
              'post_status' => 'publish', 
              'suppress_filters' => 'false', 
              'orderby' => 'post_name',
               'order' => 'ASC',
              'post__not_in' => $ids
            ) ); 
        ?>
        <hr>   
        <?php if ($is_pro){
          echo '<p>Unlimited links</p>';
        } else {
          $total_query = new WP_Query( 
            array(
              'post_type' => $post_types_final, 
              'posts_per_page' => '-1', 
              'post_status' => 'publish', 
              'suppress_filters' => 'false', 
            ) );           
          echo '<p>Links ' . $total_query->post_count . ' / max 250</p>';
          if ((int)$total_query->post_count > 250){
            echo '<p class="bold">Upgrade to pro to see the missing ' . ((int)$total_query->post_count - 250) . ' pages</p>';
          }
        }?>
        <div class="row">
          <div class="col-6">
            <p>Included links</p>
            <ol class="crawl enabled">
                <?php
                  

                while ( $enabled->have_posts() ) :
                  $enabled->the_post();
                  $id = get_the_ID();
                                ?>
                <li post_id="<?php echo $id; ?>" post_type="<?php echo get_post_type(); ?>">
                    <a href="<?php the_permalink();?>" target="_blank"><?php echo wp_make_link_relative(get_permalink()); ?></a>
                </li>
                <?php endwhile; ?>
            </ol>            
          </div>
          <div class="col-6">
            <p>Excluded links</p>
            <ol class="crawl disabled">
                <?php
                  

                while ( $disabled->have_posts() ) :
                  $disabled->the_post();
                  $id = get_the_ID();
                                ?>
                <li post_id="<?php echo $id; ?>" post_type="<?php echo get_post_type(); ?>" class="selected cant-change">
                    <a href="<?php the_permalink();?>" target="_blank"><?php echo wp_make_link_relative(get_permalink()); ?></a>
                </li>
                <?php endwhile; ?>
            </ol>            
          </div>
        </div>
      </div>    
    </div>
  </div>
  <div class="card pro <?php echo $is_pro ? 'active' : 'not-active'; ?>">
    <div class="card-body">
          <div class="form-group">
            <h4><?php echo $is_pro ? 'You are enjoying a PRO Licence!' : 'Why upgrade to pro?'; ?></h4>
            <div question="1" class="more-info-table"><a href="#" class="close">X</a>This value does show the number of links your sitemap may have. The free version of XML Sitemap Generator is limted to 250 links.</div>
            <div question="2" class="more-info-table">
              <a href="#" class="close">X</a>XML Sitemap by Kaboom does support WPML Multilingual.
              <hr>
              <p style="margin:20px 0 0 0"><strong>Free normal version</strong></p>
              <img src="<?php echo plugins_url( 'images/Not-mult.png', dirname(__FILE__) );?>" >
              <p style="margin:20px 0 0 0"><strong>Pro Multilingual version</strong></p>
              <img src="<?php echo plugins_url( 'images/Am-Multi.png', dirname(__FILE__) );?>" >              
            </div>

            <table>
              <tr class="header">
                <td>Service</td>
                <?php echo $is_pro ? '' : '<td>Free</td>' ?>
                <td>Pro</td>
              </tr>
              <tr>
                <td>Price</td>
                <?php echo $is_pro ? '<td>Active</td>' : '<td>Free</td>' ?>
                <?php echo $is_pro ? '' : '<td><a href="https://app.kaboom.website/get-licence-token/XML-Sitemap-Generator" target="_blank">Start at €8,-</a></td>' ?>
              </tr> 
              <tr>
                <td>HTML sitemap</td>              
                <td><span class="code">[kaboom-sitemap]</span></td>
                <?php echo $is_pro ? '' : '<td></td>' ?>

              </tr>
              <tr>
                <td>Free updates</td>
                <?php echo $is_pro ? '' : '<td><i class="fas fa-check"></i></td>' ?>
                <td><i class="fas fa-check"></i></td>
              </tr>
              <tr>
                <td><i question="1" class="fas fa-question-circle"></i> Number of links</td>              
                <?php echo $is_pro ? '' : '<td>250</td>' ?>
                <td>Unlimited</td>
              </tr>
              <tr>
                <td><i question="2" class="fas fa-question-circle"></i> Multilingual support</td>
                <?php echo $is_pro ? '' : '<td><i class="fas fa-times"></i></td>' ?>
                <td><i class="fas fa-check"></i></td>
              </tr>
              <tr>
                <td>Technical support</td>
                <?php echo $is_pro ? '' : '<td><i class="fas fa-times"></i></td>' ?>
                <td><i class="fas fa-check"></i></td>
              </tr>  
              <tr>               
              <tr>
                <td>Support the developers</td>
                <?php echo $is_pro ? '' : '<td><i class="fas fa-times"></i></td>' ?>
                <td><i class="fas fa-check"></i></td>
              </tr>              
            </table>
            <table class="developers">
              <tr>
                <td><img src="<?php echo plugins_url( 'images/Chris-Kroon-SEOlab.jpg', dirname(__FILE__) );?>" style="max-width:100px"><p>Chris Kroon</p></td>
              </tr>              
            </table>            
            <?php if (!$is_pro){ ?>
              <p><a class="upgrade" href="https://app.kaboom.website/get-licence-token/XML-Sitemap-Generator" target="_blank" class="small italic">Upgrade to pro!</a></p>          
            <?php } ?>
            <div class="kaboom-header">
              <label>
                Licence key
              </label>
            </div>
            <input type="password" name="api_key" autocomplete="off" class="form-control" value="<?php echo esc_attr(get_option( 'sitemap_generator_by_kaboom_license' )); ?>">
            <?php if (isset($api_valid)){
            }else {
              echo '<p><a href="https://app.kaboom.website/get-licence-token/XML-Sitemap-Generator" target="_blank" class="small italic">Get a licence key</a></p>';
            }
            
            ?>
            
          </div>
        <?php if (!$is_pro){ ?>          
          <p class="submit">
            <input type="submit" name="submit" id="submit" autocomplete="off"class="button button-primary" value="Activate licence">
          </p> 
        <?php } ?>  
      </form>    
    </div>
  </div>
</div>
<hr style="display:inline-block;width:100%">
<?php if ($is_pro){ ?>
  <div class="card" style="max-width: calc(100% - 20px); margin: 0;">
    <div class="card-body">
      <div class="container" style="max-width:700px">
        <h3>Where is you're API key used?</h3>
        <p>Here you can see on what websites your API is used and when the latest request took place.</p>
        <ul style="list-style-type: disc;padding-left: 20px;">
          <li><span class="bold">Host:</span> the domain your token has been used. When you see /sitemap.xml behind your host, that means the sitemap.xml has been visited by a user or by a search robot.</li>
          <li><span class="bold">Latest request:</span> when the latest request took place.</li>
          <li><span class="bold">Number of requests:</span> the number of requests the host had.</li>
        </ul>
      </div>
      <iframe src="https://app.kaboom.website/token_stats/<?php echo esc_attr(get_option( 'sitemap_generator_by_kaboom_license' )); ?>"></iframe>
    </div>
  </div>
<?php } ?>
<a class="test-api-token" style="display:none;" name="test-api-token" api_token="<?php echo get_option( 'sitemap_generator_by_kaboom_license' ); ?>"><a style="display:none;"></a>
<?php

  wp_enqueue_script('jquery');
  wp_enqueue_script('kaboom_script_settings', plugins_url( 'view/script.js', dirname(__FILE__) ) );
