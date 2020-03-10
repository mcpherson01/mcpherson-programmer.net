<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$active_plugins = get_option('active_plugins');
?>
<div class="kaboom-header">
  <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/blue-dot.svg'; ?>" style="background: white;padding: 2px;border-radius: 100%;width:80px;float:left;margin-right:20px">
  <h1>Kaboom | Premium plugins with focus on:</h1>
  <p>Speed . Performance . Usability . Security</p>
</div>
<div class="wrap">
  <div class="container">
    <div class="card-body">
      <center><h2>Plugins:</h2></center>
      <div class="row">
        <div class="col-6">
          <div class="card">
            <?php if (in_array ( 'xml-sitemap-generator-by-kaboom/xml-sitemap-generator-by-kaboom.php', $active_plugins)){ $activated = true; } else { $activated = false; } ?>
            <div class="card-header">
                <?php if ($activated){ ?> 
                  <span class="badge badge-success">Activated</span><?php
                } else { ?> 
                  <span class="badge badge-warning">Not activated</span><?php
                } ?>              
              XML/HTML Sitemap Generator
            </div>
            <div class="card-body">
              <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/sitemap-generator-logo.svg'; ?>" width="64px" style="float:left;margin-right:10px">
              <p>A plugin that generate a XML/HTML sitemap for your WordPress site. HTML sitemap: [kaboom-sitemap] for your XML sitemap visit /sitemap.xml</p>
              <p>
                <?php if ($activated){
                  ?> <a href="/wp-admin/admin.php?page=XML+Sitemap+Generator+By+Kaboom" class="btn btn-success">Manage Sitemap</a> <?php
                } else {
                  ?><a href="/wp-admin/plugin-install.php?s=XML+Sitemap+Generator+By+Kaboom&tab=search&type=term" class="btn btn-primary">Install plugin</a> <?php
                } ?>
              </p>
            </div>
          </div>
        </div>
       <div class="col-6">
          <div class="card">
            <?php if (in_array ( 'kaboom-send-secrets/send-secrets-by-kaboom.php', $active_plugins)){ $activated = true; } else { $activated = false; } ?>
            <div class="card-header">
                <?php if ($activated){ ?> 
                  <span class="badge badge-success">Activated</span><?php
                } else { ?> 
                  <span class="badge badge-warning">Not activated</span><?php
                } ?>              
              Kaboom Send Secrets
            </div>
            <div class="card-body">
              <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/send-secrets.png'; ?>" width="64px" style="float:left;margin-right:10px">
              <p>This plugin makes it possible to send secrets to your clients. You use the shortcode [stand_alone_send_secret], there will appear an input field to send the information to your client.</p>
              <p>
                <?php if ($activated){
                  ?> <a href="/wp-admin/admin.php?page=Send+Secrets" class="btn btn-success">Settings</a> <?php
                } else {
                  ?><a href="/wp-admin/plugin-install.php?s=Kaboom+Send+Secrets&tab=search&type=term" class="btn btn-primary">Install plugin</a> <?php
                } ?>
              </p>
            </div>
          </div>
        </div>        
        <div class="col-6 development">
          <div class="card">
            <?php if (in_array ( 'form-email-catcher-by-kaboom/form-email-catcher-by-kaboom.php', $active_plugins)){ $activated = true; } else { $activated = false; } ?>
            <div class="card-header">
              <?php if ($activated){ ?> 
                <span class="badge badge-success">Activated</span><?php
              } else { ?> 
                <span class="badge badge-danger">In development, not availible yet.</span><?php
              } ?>                  
              Form Email Catcher
            </div>
            <div class="card-body">
              <p> Catch all your form submissions and save them at a save place for you. Send a SMS or WhatsApp message when you receive a new form submission.</p>
            </div>
          </div>
        </div>        
      </div>
    </div>
  </div>
</div>
