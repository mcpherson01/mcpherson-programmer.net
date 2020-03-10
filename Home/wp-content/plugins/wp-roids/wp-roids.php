<?php
/*
Plugin Name: WP Roids
Description: Fast AF Caching for WordPress!
Version: 3.2.0
Author: Philip K. Meadows
Author URI: https://philmeadows.com
Copyright: Philip K. Meadows, All rights reserved
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0-standalone.html

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'WPRoidsPhil' ) )
{
	
	class WPRoidsPhil
	{
		
		private static $instance = NULL;
		private $className;
		private $pluginName;
		private $pluginStrapline;
		private $textDomain;
		private $debug;
		private $settings;
		private $settingCacheHtml;
		private $settingMinifyHtml;
		private $settingDeferJs;
		private $settingCompressImages;
		private $settingCacheCdn;
		private $settingIgnoredFolders;
		private $settingFlushSchedule;
		private $settingCreditLink;
		private $cacheDir;
		private $imgCache;
		private $compressionLevelJpeg;
		private $compressionLevelPng;
		private $assetsCache;
		private $assetsCacheFolder;
		private $postsCache;
		private $postsCacheFolder;
		private $fileTypes;
		private $earlyAssets;
		private $lateAssets;
		private $uri;
		private $protocol;
		private $domainName;
		private $siteUrl;
		private $rewriteBase;
		private $rootDir;
		private $styleFile;
		private $coreScriptFile;
		private $scriptFile;
		private $theme;
		private $timestamp;
		private $jsDeps;
		private $nonceName;
		private $nonceAction;
		private $cachingPlugins;
		private $conflictingPlugins;
		
		/**
		* Our constructor
		*/
		public function __construct()
		{			
			$this->className = get_class();
			$this->pluginName = 'WP Roids';
			$this->pluginStrapline = 'Fast AF Minification and Caching for WordPress';
			$this->textDomain = 'pkmwprds';
			
			// debug
			$this->debug = FALSE;
			
			// settings
			$this->settingCacheHtml = TRUE;
			$this->settingMinifyHtml = TRUE;
			$this->settingDeferJs = TRUE;
			$this->settingCompressImages = TRUE;
			$this->compressionLevelJpeg = 15;
			$this->compressionLevelPng = 50;
			$this->settingCacheCdn = TRUE;
			$this->settingFlushSchedule = 'daily';
			$this->settingCreditLink = FALSE;
			$this->settings = get_option( $this->textDomain.'_settings', NULL );
			if( $this->settings !== NULL )
			{
				if( intval( $this->settings['cache']['disabled'] ) === 1 ) $this->settingCacheHtml = FALSE;
				if( intval( $this->settings['html']['disabled'] ) === 1 ) $this->settingMinifyHtml = FALSE;
				if( intval( $this->settings['defer']['disabled'] ) === 1 ) $this->settingDeferJs = FALSE;
				if( intval( $this->settings['imgs']['disabled'] ) === 1 )
				{
					$this->settingCompressImages = FALSE;
					if( is_dir( $this->imgCache ) ) $this->recursiveRemoveDirectory( $this->imgCache );
				}
				if( isset( $this->settings['imgs-quality-jpeg']['value'] ) && intval( $this->settings['imgs-quality-jpeg']['value'] ) !== intval( $this->compressionLevelJpeg ) )
				{
					$this->compressionLevelJpeg = intval( $this->settings['imgs-quality-jpeg']['value'] );
				}
				if( isset( $this->settings['imgs-quality-png']['value'] ) && intval( $this->settings['imgs-quality-png']['value'] ) !== intval( $this->compressionLevelPng ) )
				{
					$this->compressionLevelPng = intval( $this->settings['imgs-quality-png']['value'] );
				}
				if( intval( $this->settings['cdn']['disabled'] ) === 1 ) $this->settingCacheCdn = FALSE;
				
				if( $this->settings['debug']['value'] === 'enabled' ) $this->debug = TRUE;
				if( isset( $this->settings['schedule']['value'] ) && $this->settings['schedule']['value'] === 'disabled' )
				{
					$this->settingFlushSchedule = FALSE;
					// kill the schedule
					$scheduleTimestamp = wp_next_scheduled( $this->textDomain . '_flush_schedule' );
					if( $scheduleTimestamp !== FALSE )
					{
						wp_unschedule_event( $scheduleTimestamp, $this->textDomain . '_flush_schedule' );
					}
				}
				if( isset( $this->settings['schedule']['value'] ) && $this->settings['schedule']['value'] !== 'disabled' )
				{					
					// set event to flush posts
					$this->settingFlushSchedule = $this->settings['schedule']['value'];
					if( ! wp_next_scheduled( $this->textDomain . '_flush_schedule' ) )
					{
					    wp_schedule_event( time(), $this->settingFlushSchedule, $this->textDomain . '_flush_schedule' );
					}				
				}
				
				$this->settingIgnoredFolders = array();
				foreach( $this->settings as $key => $settingArray )
				{
					if( $key === 'theme' && intval( $settingArray['disabled'] ) === 1 ) $this->settingIgnoredFolders[] = 'themes';
					if( $key !== 'html' && $key !== 'cache' && $key !== 'cdn' && $key !== 'theme' )
					{
						$parts = explode( '/', $key );
						$this->settingIgnoredFolders[] = $parts[0];
					}
				}
				if( $this->settings['credit']['value'] === 'enabled' ) $this->settingCreditLink = TRUE;
				
			} // END if( $this->settings !== NULL )
			
			// vars
			if( ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 )
			{		
				$this->protocol = 'https://';
			}
			else
			{
				$this->protocol = 'http://';
			}
			$this->rootDir = $_SERVER['DOCUMENT_ROOT'] . str_replace( $this->protocol . $_SERVER['HTTP_HOST'], '', $this->siteUrl );
			// fix for ridiculous 1&1 directory lookup bug
			if( strpos( $this->rootDir, '/kunden' ) !== FALSE )
			{
				$this->rootDir = str_replace( '/kunden', '', $this->rootDir );
			}
			$this->fileTypes = array( 'css', 'core-js', 'js' );
			$this->earlyAssets = array( 'css', 'core-js' );
			$this->lateAssets = array( 'js' );
			$this->siteUrl = site_url();
			$this->domainName = $_SERVER['HTTP_HOST'];
			$this->rewriteBase = str_replace( $this->protocol . $this->domainName, '', $this->siteUrl );
			if( strpos( $this->domainName, 'www.' ) === 0 )
			{
				$this->domainName = substr( $this->domainName, 4 );
			}
			$this->uri = str_replace( $this->rewriteBase, '', $_SERVER['REQUEST_URI'] );
			$this->cacheDir = $this->rootDir . '/wp-roids-cache';
			$this->imgCache = $this->cacheDir . '/img';
			$this->assetsCache = $this->cacheDir . '/' . 'assets' . $this->rewriteBase;
			$this->assetsCacheFolder = str_replace( $this->rootDir . '/', '', $this->assetsCache );
			$this->postsCache = $this->cacheDir . '/' . 'posts' . $this->rewriteBase;
			$this->postsCacheFolder = str_replace( $this->rootDir . '/', '', $this->postsCache );
			$this->styleFile = $this->textDomain . '-styles.min';
			$this->coreScriptFile = $this->textDomain . '-core.min';
			$this->scriptFile = $this->textDomain . '-scripts.min';
			$this->theme = wp_get_theme();
			$this->timestamp = '-' . substr( time(), 0, 8 );
			$this->jsDeps = array();
			$this->nonceName = $this->textDomain . '_nonce';
			$this->nonceAction = 'do_' . $this->textDomain;
			
			$this->conflictingPlugins = array(
				['slug' => 'nextgen-gallery/nggallery.php', 'name' => 'WordPress Gallery Plugin – NextGEN Gallery', 'ref' => 'https://wordpress.org/support/topic/all-marketing-crappy-product-does-not-even-follow-good-coding-practices'],
			);
			$this->cachingPlugins = array(
				['slug' => 'autoptimize/autoptimize.php', 'name' => 'Autoptimize'],
				['slug' => 'breeze/breeze.php', 'name' => 'Breeze'],
				['slug' => 'cache-control/cache-control.php', 'name' => 'Cache-Control'],
				['slug' => 'cache-enabler/cache-enabler.php', 'name' => 'Cache Enabler'],
				['slug' => 'cachify/cachify.php', 'name' => 'Cachify'],
				['slug' => 'comet-cache/comet-cache.php', 'name' => 'Comet Cache'],
				['slug' => 'dessky-cache/dessky-cache.php', 'name' => 'Dessky Cache'],
				['slug' => 'fast-velocity-minify/fvm.php', 'name' => 'Fast Velocity Minify'],
				['slug' => 'hummingbird-performance/wp-hummingbird.php', 'name' => 'Hummingbird'],
				['slug' => 'sg-cachepress/sg-cachepress.php', 'name' => 'SG Optimizer'],
				['slug' => 'hyper-cache/plugin.php', 'name' => 'Hyper Cache'],
				['slug' => 'hyper-cache-extended/plugin.php', 'name' => 'Hyper Cache Extended'],
				['slug' => 'litespeed-cache/litespeed-cache.php', 'name' => 'LiteSpeed Cache'],
				['slug' => 'simple-cache/simple-cache.php', 'name' => 'Simple Cache'],			
				['slug' => 'w3-total-cache/w3-total-cache.php', 'name' => 'W3 Total Cache'],
				['slug' => 'wp-fastest-cache/wpFastestCache.php', 'name' => 'WP Fastest Cache'],
				['slug' => 'wp-speed-of-light/wp-speed-of-light.php', 'name' => 'WP Speed of Light'],
				['slug' => 'wp-super-cache/wp-cache.php', 'name' => 'WP Super Cache'],
			);
			
			// do we have the necessary stuff?
			if( ! $this->checkRequirements() )
			{
				// ensures .htaccess is reset cleanly
				$this->deactivate();
				return FALSE;
			}
			else
			{
				// install
				register_activation_hook( __FILE__, array( $this, 'install' ) );
				add_action( 'init', array( $this, 'sentry' ) );
				add_filter( 'cron_schedules', array( $this, 'addCronIntervals' ) );
				add_action( $this->textDomain . '_flush_schedule', array( $this, 'flushPostCache' ) );
				remove_action( 'wp_head', 'wp_generator' );
				add_action( 'get_header', array( $this, 'minifyPost' ) );
				add_action( 'wp_head', array( $this, 'cacheThisPost'), PHP_INT_MAX - 1 );
				add_action( 'wp_enqueue_scripts', array( $this, 'doAllAssets' ), PHP_INT_MAX - 2 );
				add_filter( 'script_loader_src', array( $this, 'removeScriptVersion' ), 15, 1 );
				add_filter( 'style_loader_src', array( $this, 'removeScriptVersion' ), 15, 1 );
				add_action( 'wp', array( $this, 'htaccessFallback'), PHP_INT_MAX );
				
				// add links below plugin description on Plugins Page table
				// see: https://developer.wordpress.org/reference/hooks/plugin_row_meta/
				add_filter( 'plugin_row_meta', array( $this, 'pluginMetaLinks' ), 10, 2 );
				
				// some styles for the admin page
				add_action( 'admin_enqueue_scripts', array( $this, 'loadAdminScripts' ) );
				
				// add a link to the Admin Menu
				add_action( 'admin_menu', array( $this, 'adminMenu' ) );
				
				// add settings link
				// see: https://codex.wordpress.org/Plugin_API/Filter_Reference/plugin_action_links_(plugin_file_name)
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'pluginActionLinks' ) );
				
				// add admin bar link
				add_action('admin_bar_menu', array( $this, 'adminBarLinks' ), 1000 );
				
				// credit link in footer
				add_action( 'wp_footer', array( $this, 'creditLink') );
				
				// individual caching actions
				add_action( 'save_post', array( $this, 'cacheDecider') );
				add_action( 'comment_post', array( $this, 'cacheComment') );
				
				// cache flushing actions
				add_action( 'activated_plugin', array( $this, 'flushWholeCache' ), 10, 2 );
				add_action( 'deactivated_plugin', array( $this, 'flushWholeCache' ), 10, 2 );
				add_action( 'switch_theme', array( $this, 'reinstall' ), 1000 );
				add_action( 'wp_create_nav_menu', array( $this, 'flushPostCache' ) );
				add_action( 'wp_update_nav_menu', array( $this, 'flushPostCache' ) );
				add_action( 'wp_delete_nav_menu', array( $this, 'flushPostCache' ) );
		        add_action( 'create_term', array( $this, 'flushPostCache' ) );
		        add_action( 'edit_terms', array( $this, 'flushPostCache' ) );
		        add_action( 'delete_term', array( $this, 'flushPostCache' ) );
		        add_action( 'add_link', array( $this, 'flushPostCache' ) );
		        add_action( 'edit_link', array( $this, 'flushPostCache' ) );
		        add_action( 'delete_link', array( $this, 'flushPostCache' ) );
		        
		        // deactivate
				register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
			
				// ONLY USE IF DESPERATE! Prints data to bottom of PUBLIC pages!
				//if( $this->debug === TRUE ) add_action( 'wp_footer', array( $this, 'wpRoidsDebug'), 100 );
			
			}			
			
		} // END __construct()
		
		/**
		* DEV use only!
		* 
		* @return string: Your debug info
		*/
		private function writeLog( $message )
		{
			if( $this->debug === TRUE )
			{
				$fh = fopen( __DIR__ . '/log.txt', 'ab' );
				fwrite( $fh, date('d/m/Y H:i:s') . ': ' . $message . "\n____________________\n\n" );
				fclose( $fh );
			}
		}
		
		public function wpRoidsDebug()
		{
			if( $this->debug === TRUE )
			{
				$output = array('wpRoidsDebug initialised!...');
				if( file_exists( __DIR__ . '/log.txt' ) )
				{
					$theLog = htmlentities( file_get_contents( __DIR__ . '/log.txt' ) );			
					// strip excessive newlines
					$theLog = preg_replace( '/\r/', "\n", $theLog );
					$theLog = preg_replace( '/\n+/', "\n", $theLog );
					// wrap errors in a class
					$theLog = preg_replace( '~^(.*ERROR:.*)$~m', '<span class="error">$1</span>', $theLog );
					$output['errorsFound'] = substr_count( $theLog, 'ERROR:' );
					$output['logfile'] = $theLog;
				}
				echo '<pre class="debug">WP Roids Debug...'. "\n\n" . print_r( $output, TRUE ) .'</pre>';				
			}
		}
		/**
		* END DEV use only!
		*/
		
		/**
		* Basically a boolean strpos(), but checks an array of strings for occurence
		* @param string $haystack
		* @param array $needle
		* @param int $offset
		* 
		* @return bool
		*/
		private function strposa( $haystack, $needle, $offset = 0 )
		{
		    foreach( $needle as $lookup )
		    {
		        if( strpos( $haystack, $lookup, $offset ) !== FALSE )
		        {
		        	return TRUE; // stop on first true result
				}
		    }
		    return FALSE;
		    
		} // END strposa()
		
		/**
		* Add to built in WordPress CRON schedules
		* see: https://developer.wordpress.org/plugins/cron/understanding-wp-cron-scheduling/
		* @param array $schedules
		* 
		* @return array $schedules
		*/
		public function addCronIntervals( $schedules )
		{
			$schedules['every_five_minutes'] = array(
		        'interval' => 300,
		        'display'  => esc_html__( 'Every Five Minutes' ),
		    );
			$schedules['weekly'] = array(
		        'interval' => 604800,
		        'display'  => esc_html__( 'Weekly' ),
		    );
		    return $schedules;
		}
		
		/**
		* Check dependencies
		*/
		public function checkRequirements()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'checkRequirements() running...');
			$requirementsMet = TRUE;
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			require_once( ABSPATH . '/wp-includes/pluggable.php' );
			
			// we need cURL active
			if( ! in_array( 'curl', get_loaded_extensions() ) )
			{
				if( $this->debug === TRUE ) $this->writeLog( 'ERROR: cURL NOT available!');
				add_action( 'admin_notices', array( $this, 'messageCurlRequired' ) );
				$requirementsMet = FALSE;
			}
			
			// .htaccess needs to be writable, some security plugins disable this
			// only perform this check when an admin is logged in, or it'll deactivate the plugin :/
			if( current_user_can( 'install_plugins' ) )
			{		
				$htaccess = $this->rootDir . '/.htaccess';
				$current = file_get_contents( $htaccess );
				$starttext = '### BEGIN WP Roids - DO NOT REMOVE THIS LINE';
				$endtext = '### END WP Roids - DO NOT REMOVE THIS LINE' . "\n\n";
				if( strpos( $current, $starttext ) === FALSE && strpos( $current, $endtext ) === FALSE )
				{
					$fh = fopen( $htaccess, 'wb' );
					$isHtaccessWritable = fwrite( $fh, $current );
					fclose( $fh );
					if( $isHtaccessWritable === FALSE )
					{				
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: `.htaccess` NOT writable!');
						add_action( 'admin_notices', array( $this, 'messageHtNotWritable' ) );
						$requirementsMet = FALSE;
					}
				}
			}
			
			// we do not want caching plugins active
			$cachingDetected = FALSE;
			foreach( $this->cachingPlugins as $cachingPlugin )
			{
				if( is_plugin_active( $cachingPlugin ) )
				{
					$cachingDetected = TRUE;
				}
			}
			if( $cachingDetected === TRUE )
			{
				add_action( 'admin_notices', array( $this, 'messageCachingDetected' ) );
				$requirementsMet = FALSE;
				if( $this->debug === TRUE ) $this->writeLog( 'ERROR: Another caching plugin detected!');
			}
			
			// we do not want conflicting plugins active
			$conflictDetected = FALSE;
			foreach( $this->conflictingPlugins as $conflictingPlugin )
			{
				if( is_plugin_active( $conflictingPlugin['slug'] ) )
				{
					$conflictDetected = TRUE;
				}
			}
			if( $conflictDetected === TRUE )
			{
				add_action( 'admin_notices', array( $this, 'messageConflictDetected' ) );
				$requirementsMet = FALSE;
				if( $this->debug === TRUE ) $this->writeLog( 'ERROR: Conflicting plugin(s) detected!');
			}
			
			// kill plugin activation
			if( $requirementsMet === FALSE ) deactivate_plugins( plugin_basename( __FILE__ ) );
			
			if( $this->debug === TRUE ) $this->writeLog( 'checkRequirements() SUCCESS!');
			return $requirementsMet;
			
		} // END checkRequirements()
		
		/**
		* Called on plugin activation - sets things up
		* @return void
		*/
		public function install()
		{
			if( $this->debug === TRUE ) $this->writeLog( $this->pluginName . ' install() running');
			
			// create cache directory
			if( ! is_dir( $this->cacheDir ) ) mkdir( $this->cacheDir, 0755 );
			
			// .htaccess
			$htaccess = $this->rootDir . '/.htaccess';
			if( file_exists( $htaccess ) )
			{
				$desiredPerms = fileperms( $htaccess );
				chmod( $htaccess, 0644 );
				$current = file_get_contents( $htaccess );
				$starttext = '### BEGIN WP Roids - DO NOT REMOVE THIS LINE';
				$endtext = '### END WP Roids - DO NOT REMOVE THIS LINE' . "\n\n";
				if( strpos( $current, $starttext ) === FALSE && strpos( $current, $endtext ) === FALSE )
				{
					// take a backup
					$backup = __DIR__ . '/ht-backup.txt';
					$fh = fopen( $backup, 'wb' );
					fwrite( $fh, $current );
					fclose( $fh );
					chmod( $backup, 0600 );
					
					// edit .htaccess
					$assetsCacheFolder = str_replace( $this->rootDir . '/', '', $this->assetsCache );
					$fullPostsCacheFolder = str_replace( $_SERVER['DOCUMENT_ROOT'] . '/', '', $this->postsCache );
					$fullPostsCacheFolder = ltrim( str_replace( $this->rootDir, '', $fullPostsCacheFolder ), '/' );
					$fullImagesCacheFolder = str_replace( $_SERVER['DOCUMENT_ROOT'] . '/', '', $this->imgCache );
					$fullImagesCacheFolder = ltrim( str_replace( $this->rootDir, '', $fullImagesCacheFolder ), '/' );
					$postsCacheFolder = str_replace( $this->rootDir . '/', '', $this->postsCache );
					$imagesCacheFolder = str_replace( $this->rootDir . '/', '', $this->imgCache );
					$additional = str_replace( '[[DOMAIN_NAME]]', $this->domainName, file_get_contents( __DIR__ . '/ht-template.txt' ) );
					$additional = str_replace( '[[WP_ROIDS_REWRITE_BASE]]', $this->rewriteBase, $additional );
					$additional = str_replace( '[[WP_ROIDS_ASSETS_CACHE]]', $assetsCacheFolder, $additional );
					$additional = str_replace( '[[WP_ROIDS_FULL_POSTS_CACHE]]', $fullPostsCacheFolder, $additional );
					$additional = str_replace( '[[WP_ROIDS_ALT_FULL_POSTS_CACHE]]', $this->postsCache, $additional );
					$additional = str_replace( '[[WP_ROIDS_FULL_IMAGES_CACHE]]', $fullImagesCacheFolder, $additional );
					$additional = str_replace( '[[WP_ROIDS_ALT_FULL_IMAGES_CACHE]]', $this->imgCache, $additional );
					$additional = str_replace( '[[WP_ROIDS_POSTS_CACHE]]', $postsCacheFolder, $additional );
					$additional = str_replace( '[[WP_ROIDS_IMAGES_CACHE]]', $imagesCacheFolder, $additional );
					$startpoint = strpos( $current, '# BEGIN WordPress' );
					$new = substr_replace( $current, $additional . "\n\n", $startpoint, 0 );
					$fh = fopen( $htaccess, 'wb' );
					fwrite( $fh, $new );
					fclose( $fh );
					chmod( $htaccess, $desiredPerms );
					if( $this->debug === TRUE ) $this->writeLog( '`.htaccess` rewritten with: "' . $new . '"');
				}
    		
			} // END if htaccess
			
			// clear log
			if( file_exists( __DIR__ . '/log.txt' ) )
			{
				unlink( __DIR__ . '/log.txt' );
			}
			
			// set event to flush posts
			if( $this->settingFlushSchedule !== FALSE )
			{
				if( ! wp_next_scheduled( $this->textDomain . '_flush_schedule' ) )
				{
				    wp_schedule_event( time(), $this->settingFlushSchedule, $this->textDomain . '_flush_schedule' );
				}
			}
			
		} // END install()
		
		/**
		* Ongoing check all is healthy
		* 
		* @return void
		*/
		public function sentry()
		{
			if( current_user_can( 'install_plugins' ) )
			{
				if( $this->debug === TRUE ) $this->writeLog( 'sentry() running...');
				$requirementsMet = TRUE;
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				if( is_plugin_active( plugin_basename( __FILE__ ) )  )
				{
					// check .htaccess is still legit for us
					if( $this->debug === TRUE ) $this->writeLog( 'sentry() running: ' . $this->pluginName . ' is active!' );	
					$htaccess = $this->rootDir . '/.htaccess';
					$current = file_get_contents( $htaccess );
					
					$myRules = TRUE;
					$starttext = '### BEGIN WP Roids - DO NOT REMOVE THIS LINE';
					$endtext = '### END WP Roids - DO NOT REMOVE THIS LINE' . "\n\n";
					if( strpos( $current, $starttext ) === FALSE && strpos( $current, $endtext ) === FALSE ) $myRules = FALSE;
					$newCookieCheck = 'RewriteCond %{HTTP:Cookie} !^.*(comment_author_|wordpress_logged_in|wp-postpass_).*$';
					if( strpos( $current, $newCookieCheck ) === FALSE ) $myRules = FALSE;
					
					$myOldRules = FALSE;
					$oldstarttext = '# BEGIN WP Roids - DO NOT REMOVE THIS LINE';
					$oldendtext = '# END WP Roids - DO NOT REMOVE THIS LINE' . "\n\n";
					if( strpos( $current, $oldstarttext ) !== FALSE && strpos( $current, $oldendtext ) !== FALSE ) $myOldRules = TRUE;
					
					if( $myRules === FALSE || ( $myRules === FALSE && $myOldRules === TRUE ) )
					{
						$requirementsMet = FALSE;
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: sentry() running: `.htaccess` is missing rules!' );	
					}
				
					// check cache directories
					if( ! is_dir( $this->cacheDir ) )
					{
						$requirementsMet = FALSE;
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: sentry() running: cache folder not found!' );
					}
					
					if( $requirementsMet === FALSE )
					{
						$this->deactivate();
						$this->install();
					}
					
				} // END we are active
				
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: sentry() found ' . $this->pluginName . ' is NOT active!');
				}
				
			} // END current user is admin
		} // END sentry()
		
		/**
		* Called on any plugin activation - resets things up and returns to request origin
		* @return void
		*/
		public function reinstall()
		{
			deactivate_plugins( plugin_basename( __FILE__ ) );
			activate_plugins( plugin_basename( __FILE__ ), $this->protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			
			// clear log
			if( file_exists( __DIR__ . '/log.txt' ) )
			{
				unlink( __DIR__ . '/log.txt' );
			}
			
			if( $this->debug === TRUE ) $this->writeLog( 'reinstall() executed!');
		} // END reinstall()
		
		/**
		* Fired when a page is browsed
		* @return void
		*/
		public function htaccessFallback()
		{
			global $post, $wp_query;
			if( $this->debug === TRUE ) $this->writeLog( 'htaccessFallback() running... $wp_query = "' . print_r( $wp_query, TRUE ) . '"' );
			$viableBrowse = TRUE;
			if( strpos( $wp_query->request, 'SQL_CALC_FOUND_ROWS' ) !== FALSE ) $viableBrowse = FALSE;
			if( ! $post instanceof WP_Post ) $viableBrowse = FALSE;
			if( ! empty( $_COOKIE ) )
			{
				$negativeCookieStrings = array( 'comment_author_', 'wordpress_logged_in', 'postpass_' );
				foreach( $_COOKIE as $cookieKey => $cookieValue )
				{
					foreach( $negativeCookieStrings as $negativeCookieString )
					{
						if( strpos( $cookieKey, $negativeCookieString ) !== FALSE )
						{
							$viableBrowse = FALSE;
							break;
						}
					}
				}
			}
			if( $_POST ) $viableBrowse = FALSE;
			if( $_SERVER['QUERY_STRING'] !== '' ) $viableBrowse = FALSE;
			if( $this->isViablePost( $post ) && $viableBrowse === TRUE )
			{
				// does a cache file exist?
				$thePermalink = get_permalink( $post->ID );
				$isHome = FALSE;
				if( $thePermalink === $this->siteUrl . '/' ) $isHome = TRUE;
				
				if( $isHome === FALSE )
				{
					$cacheFilePath = str_replace( $this->siteUrl, '', $thePermalink );
					$fullCacheFilePath = $this->postsCache . '/' . rtrim( ltrim( $cacheFilePath, '/' ), '/' );
					$cacheFile = $fullCacheFilePath . '/index.html';	
				}
				else
				{
					$cacheFile = $this->postsCache . '/index.html';
				}
				
				if( file_exists( $cacheFile ) )
				{
					// cache file exists, yet .htaccess did NOT rewrite :/
					if( $this->debug === TRUE ) $this->writeLog( 'htaccessFallback() invoked for file: `' . $cacheFile . '`!');
					$fileModified = @filemtime( $cacheFile );
					if( $fileModified !== FALSE )
					{
						$oldestAllowed = ( time() - 300 );
						if( intval( $fileModified ) > intval( $oldestAllowed ) )
						{
							// file is cool, go get it
							$cacheContent = file_get_contents( $cacheFile );
							$cacheContent .= "\n" . '<!-- WP Roids cache file served by PHP script as `.htaccess` rewrite failed.' . "\n";
							if( $wp_query->is_home() || $wp_query->is_front_page() )
							{
								$cacheContent .= 'BUT! This is your home page, SOME hosts struggle with `.htaccess` rewrite on the home page only.' . "\n" . 'Check one of your inner Posts/Pages and see what the comment is there... -->';
							}
							else
							{
								$cacheContent .= 'Contact your host for explanation -->';
							}
							
							die( $cacheContent );
						}
						else
						{
							$this->cachePost( $post->ID );
						}
					}
					else
					{
						$this->cachePost( $post->ID );
					}
					
				} // END cache file exists
				
			} // END isViablePost
			
		} // END htaccessFallback()
		
		/**
		* Is the request a cacheworthy Post/Page?
		* @param bool $assets: Whether this is being called by the asset crunching functionality
		* @param obj $post: Instance of WP_Post object
		* 
		* @return bool
		*/
		private function isViablePost( $post, $assets = FALSE )
		{
			if( is_object( $post ) )
			{				
				$noCookies = TRUE;
				$negativeCookieStrings = array( 'comment_author_', 'wordpress_logged_in', 'postpass_' );
				foreach( $_COOKIE as $cookieKey => $cookieValue )
				{
					foreach( $negativeCookieStrings as $negativeCookieString )
					{
						if( strpos( $cookieKey, $negativeCookieString ) !== FALSE )
						{
							$noCookies = FALSE;
							break;
						}
					}
				}
				
				if( ! is_admin()
					&& ( $assets === TRUE || ( ! $_POST && ! isset( $_POST['X-WP-Roids'] ) ) ) 
					&& $noCookies === TRUE
					&& ! post_password_required() 
					&& ( is_singular() || is_archive() )
					&& ( defined( 'DONOTCACHEPAGE' ) !== TRUE && intval( DONOTCACHEPAGE ) !== 1 )
					&& ! is_404() 
					&& get_post_status( $post->ID ) === 'publish' 
					)
				{
					if( $this->debug === TRUE ) $this->writeLog( 'isViablePost() running... Post ID: ' . $post->ID . ' `' . $post->post_title . '` was considered viable' );
					return TRUE;
				}
			}
			
			return FALSE;
			
		} // END isViablePost()
		
		/**
		* Caches a Post/Page
		* @param int $ID: a Post/Page ID
		* 
		* @return bool: TRUE on success, FALSE on fail
		*/
		public function cachePost( $ID )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'cachePost() running...' );
			$start = microtime( TRUE );
			if( get_post_status( $ID ) === 'publish' )
			{
				if( $this->settingCacheHtml === TRUE )
				{
					$thePermalink = get_permalink( $ID );
					$isHome = FALSE;
					if( $thePermalink === $this->siteUrl . '/' ) $isHome = TRUE;
					if( $this->debug === TRUE ) $this->writeLog( '$isHome = `' . print_r( $isHome, TRUE ) . '`' . "\n" );
					
					if( $isHome === FALSE )
					{
						$cacheFile = str_replace( $this->siteUrl, '', $thePermalink );
						$cacheFilePath = $this->postsCache . '/' . rtrim( ltrim( $cacheFile, '/' ), '/' );
						$newfile = $cacheFilePath . '/index.html';	
					}
					else
					{
						$cacheFilePath = $this->postsCache;
						$newfile = $cacheFilePath . '/index.html';
					}
					
					$data = array( 'X-WP-Roids' => TRUE );
			        $curlOptions = array(
			            CURLOPT_URL => $thePermalink,
			            CURLOPT_REFERER => $thePermalink,
						CURLOPT_POST => TRUE,
						CURLOPT_POSTFIELDS => $data,
				        CURLOPT_HEADER => FALSE,
			            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
			            CURLOPT_RETURNTRANSFER => TRUE,
			        );
					$ch = curl_init();
		    		curl_setopt_array( $ch, $curlOptions );
		    		$html = curl_exec( $ch );
		    		curl_close( $ch );
				    $executionTime = number_format( microtime( TRUE ) - $start, 5 );
		    		
		    		if( $html !== FALSE && ! empty( $html ) )
		    		{
					    // add a wee note
					    $htmlComment = "\n" . '<!-- Performance enhanced Static HTML cache file generated at ' . gmdate("M d Y H:i:s") . ' GMT by ' . $this->pluginName . ' in ' . $executionTime . ' sec -->';
						if( ! is_dir( $cacheFilePath ) )
						{
							mkdir( $cacheFilePath, 0755, TRUE );
						}
						// write the static HTML file
						$fh = fopen( $newfile, 'wb' );
						fwrite( $fh, $html . $htmlComment );
						fclose( $fh );
						if( file_exists( $newfile ) )
						{
							if( $this->debug === TRUE ) $this->writeLog( '`' . $newfile . '` written' );					
							if( $this->debug === TRUE ) $this->writeLog( 'cachePost() took ' . $executionTime . ' sec' );
							return TRUE;
						}
						else
						{
							if( $this->debug === TRUE ) $this->writeLog( 'ERROR: `' . $newfile . '`  was NOT written, grrrrr' );
							if( $this->debug === TRUE ) $this->writeLog( 'cachePost() took ' . $executionTime . ' sec' );
							return FALSE;
						}
					}
					else
					{
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: cURL FAILED to retrieve HTML, PageSpeed was over 10 seconds, may be large images or bad queries' );
						if( $this->debug === TRUE ) $this->writeLog( 'cachePost() took ' . $executionTime . ' sec' );
						return FALSE;
					}
		    		
				}
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'NOTICE: `' . $newfile . '`  was NOT written as HTML caching disabled in Settings' );
					return FALSE;
				}
				
			}
			
		} // END cachePost()
		
		/**
		* Fired when a page not in the cache is browsed
		* @return void
		*/
		public function cacheThisPost()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'cacheThisPost() running...');
			if( $this->settingCacheHtml === TRUE )
			{
				global $post;
				if( $this->isViablePost( $post ) && ! $_POST )
				{
					$start = microtime( TRUE );
					$outcome = $this->cachePost( $post->ID );
					if( $this->debug === TRUE )
					{
						if( $outcome === TRUE )
						{
							$this->writeLog( 'cacheThisPost() on `' . $post->post_title . '` took ' . number_format( microtime( TRUE ) - $start, 5 ) . ' sec' );
						}
						else
						{
							$this->writeLog( 'ERROR: cacheThisPost() on `' . $post->post_title . '` failed with FALSE response from cachePost()' );
						}
					}
				} // END post IS viable
				
			} // END $this->settingCacheHtml === TRUE
			else
			{
				$this->writeLog( 'cacheThisPost() did nothing because caching is disabled in Settings' );
			}
			
		} // END cacheThisPost()
		
		/**
		* Fired on "save_post" action
		* @param int $ID: the Post ID
		* 
		* @return void
		*/
		public function cacheDecider( $ID )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'cacheDecider() running...');
			$postObj = get_post( $ID );
			if( $postObj instanceof WP_Post )
			{
				switch( $postObj->post_status )
				{
					case 'publish':
						if( $postObj->post_password === '' )
						{
							$this->flushPostCache();
							$this->cachePost( $ID );
						}
						else
						{
							$this->flushPostCache();
						}						
						break;
					case 'inherit':
						$this->flushPostCache();
						$this->cachePost( $postObj->post_parent );
						break;
					case 'private':
					case 'trash':
						$this->flushPostCache();
						break;
					default:
						// there ain't one ;)
						break;
				}
				if( $this->debug === TRUE ) $this->writeLog( 'cacheDecider() was triggered! Got a WP_Post obj. Status was: ' . $postObj->post_status );
			}
		} // END cacheDecider()
		
		/**
		* Deletes cached version of Post/Page
		* @param int $ID: a Post/Page ID
		* 
		* @return void
		*/
		public function deleteCachePost( $ID )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() running...' );
			$thePermalink = get_permalink( $ID );
			$isHome = FALSE;
			if( $thePermalink === $this->siteUrl . '/' ) $isHome = TRUE;
			
			if( $isHome === FALSE )
			{
				if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - NOT the home page' );
				$cacheFile = str_replace( $this->siteUrl, '', str_replace( '__trashed', '', $thePermalink ) );
				$cacheFilePath = $this->postsCache . '/' . rtrim( ltrim( $cacheFile, '/' ), '/' );
				$assetFilePath = $this->assetsCache . '/' . rtrim( ltrim( $cacheFile, '/' ), '/' );
				$killfile = $cacheFilePath . '/index.html';	
			}
			else
			{
				if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - IS the home page' );
				$cacheFilePath = $this->postsCache;
				$assetFilePath = $this->assetsCache . '/' . str_replace( $this->protocol . $_SERVER['HTTP_HOST'], '', $this->siteUrl );
				$killfile = $cacheFilePath . '/index.html';
			}
			if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - $cacheFilePath = "' . $cacheFilePath . '"' );
			if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - $killfile = "' . $killfile . '"' );
			
			if( is_dir( $cacheFilePath ) )
			{
				if( $this->debug === TRUE ) $this->writeLog( 'deleteCachePost() - $cacheFilePath is_dir = TRUE' );
				if( file_exists( $killfile ) ) unlink( $killfile );
				$this->recursiveRemoveEmptyDirectory( $cacheFilePath );
			}
			
			if( is_dir( $assetFilePath ) )
			{
				$scriptFile = $this->scriptFile;
				$filenameArray = glob( $assetFilePath . '/' . $scriptFile . "*" );
				if( count( $filenameArray) === 1 && file_exists( $filenameArray[0] ) ) unlink( $filenameArray[0] );
				$this->recursiveRemoveEmptyDirectory( $assetFilePath );
			}
			
		} // END deleteCachePost()
		
		/**
		* Checks if new comment is approved and caches Post if so
		* @param int $commentId: The Comment ID
		* @param bool $commentApproved: 1 if approved OR 0 if not
		* 
		* @return void
		*/
		public function cacheComment( $commentId, $commentApproved )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'cacheComment() running...' );
			if( $commentApproved === 1 )
			{
				$theComment = get_comment( $commentId );
				if( is_object( $theComment ) ) $this->cachePost( $theComment->comment_post_ID );
			}
			
		} // END cacheComment()
		
		/**
		* Minifies HTML string
		* @param string $html: Some HTML
		* 
		* @return string $html: Minified HTML
		*/
		private function minifyHTML( $html )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'minifyHTML() running...' );
			
			// Defer the main JS file if enabled
			if( $this->settingDeferJs === TRUE )
			{
				$pattern = "~^<script\s.*src='(" . $this->siteUrl . '.*' . $this->scriptFile . "-\d+\.js)'.*</script>$~m";
				$html = preg_replace( $pattern, '<script type="text/javascript"> function downloadJSAtOnload() { var element = document.createElement("script"); element.type = "text/javascript"; element.src = "$1"; document.body.appendChild(element); } if (window.addEventListener) window.addEventListener("load", downloadJSAtOnload, false); else if (window.attachEvent) window.attachEvent("onload", downloadJSAtOnload); else window.onload = downloadJSAtOnload; </script>', $html );
			}
			
			// Compress images if enabled
			if( $this->settingCompressImages === TRUE )
			{
				if( ! is_dir( $this->imgCache ) ) mkdir( $this->imgCache, 0755 );
				if( class_exists( 'DOMDocument' ) )
				{
					$dom = new DOMDocument;
					$dom->loadHTML( $html );
					foreach( $dom->getElementsByTagName( 'img' ) as $node )
					{
					    if( $node->hasAttribute( 'src' ) )
					    {
							$src = $node->getAttribute( 'src' );
							if( strpos( $src, $this->siteUrl ) !== FALSE )
							{
								$siteUrl = str_replace( ['https:','http:'], '', $this->siteUrl );
								$path = rtrim( ABSPATH, '/' );
								$filePath = str_replace( [$this->siteUrl, $siteUrl], $path, $src );
								if( file_exists( $filePath ) )
								{
									// see if we have already compressed and cached this image
									$filename = basename( $filePath );
									$cachedImage = $this->imgCache . '/' . $filename;
									if( ! file_exists( $cachedImage ) )
									{
										$image = wp_get_image_editor( $filePath );
										if( ! is_wp_error( $image ) )
										{
											$quality = $image->get_quality();
											if( is_numeric( $quality ) && intval( $quality ) > $this->compressionLevelJpeg )
											{
												if( substr( $filePath, -4, 4 ) === '.jpg' || substr( $filePath, -5, 5 ) === '.jpeg' )
												{
													$compress = $image->set_quality( $this->compressionLevelJpeg );
												}
												if( substr( $filePath, -4, 4 ) === '.png' )
												{
													$compress = $image->set_quality( $this->compressionLevelPng );
												}
												
												if( isset( $compress ) && ! is_wp_error( $compress ) )
												{
													$image->save( $cachedImage );
													if( $this->debug === TRUE ) $this->writeLog( 'I compressed and cached an image! src: "' . $src . '" | xtn: "' . $xtn . '" | filepath: "' . $filePath . '" | original quality: "' . $quality . '" | basename: "' . $filename . '" | cache file: "' . $cachedImage . '"' );
												}
												else
												{
													if( $this->debug === TRUE ) $this->writeLog( 'ERROR: if( isset( $compress ) && ! is_wp_error( $compress ) ) fail! Returned FALSE! ~ NOT isset OR WP_Error thrown!' );
												}
											}
											else
											{
												if( $this->debug === TRUE ) $this->writeLog( 'NOTICE:if( is_numeric( $quality ) && intval( $quality ) > $this->compressionLevel ) fail! Returned FALSE! ~Locally hosted image ALREADY compressed beyond threshold!' );
											}
										}
										else
										{
											if( $this->debug === TRUE ) $this->writeLog( 'ERROR: if( ! is_wp_error( $image ) ) fail! Returned FALSE! ~ WP_Error thrown!' );
										}
									}
									else
									{
										if( $this->debug === TRUE ) $this->writeLog( 'NOTICE: if( ! file_exists( $cachedImage ) ) fail! Returned FALSE! ~ Locally hosted CACHED image found' );
									}
								}
								else
								{
									if( $this->debug === TRUE ) $this->writeLog( 'WARNING: if( file_exists( $filePath ) ) fail! Returned FALSE! ~ Locally hosted image NOT found' );
								}
							}
							else
							{
								if( $this->debug === TRUE ) $this->writeLog( 'NOTICE: if( strpos( "src", $this->siteUrl ) !== FALSE ) fail! Returned FALSE! ~ Remotely hosted image found' );
							}
						}
						else
						{
							if( $this->debug === TRUE ) $this->writeLog( 'WARNING: if( $node->hasAttribute( "src" ) ) fail! Returned FALSE!' );
						}
						
					} // END foreach( $dom->getElementsByTagName( 'img' ) as $node )
					
				}
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'WARNING: class_exists( "DOMDocument" ) fail! Returned FALSE!' );
				}
				
			} // END if( $this->settingCompressImages === TRUE )
			
			// Minify HTML if enabled
			if( $this->settingMinifyHtml === TRUE )
			{
				// see: http://stackoverflow.com/questions/5312349/minifying-final-html-output-using-regular-expressions-with-codeigniter			
				$regex = '~(?>[^\S ]\s*|\s{4,})(?=[^<]*+(?:<(?!/?(?:textarea|pre|span|a)\b)[^<]*+)*+(?:<(?>textarea|pre|span|a)\b|\z))~Six';
				// minify
				$html = preg_replace( $regex, NULL, $html );
				
			    // remove html comments, but not conditionals
			    $html = preg_replace( "~<!--(?!<!)[^\[>].*?-->~", NULL, $html );
			    
			    if( $html === NULL || $html === '' )
			    {
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: minifyHTML() fail! PCRE Error! File too big!' );
			    	exit( 'PCRE Error! File too big.');
			    }
				global $post, $wp_query;
				if( $this->isViablePost( $post ) && ! $_POST && strpos( $wp_query->request, 'SQL_CALC_FOUND_ROWS' ) === FALSE )
				{
					$html .= "\n" . '<!--' . "\n" . 'Minified web page generated at ' . gmdate("M d Y H:i:s") . ' GMT by ' . $this->pluginName . "\n" . 'This page is NOT a cached static HTML file YET, but it should be on its next request if caching is enabled in Settings :)' . "\n" . '-->';
				}
				if( ( ! $this->isViablePost( $post ) && ! $_POST ) || strpos( $wp_query->request, 'SQL_CALC_FOUND_ROWS' ) !== FALSE )
				{
					$html .= "\n" . '<!--' . "\n" . 'Minified web page generated at ' . gmdate("M d Y H:i:s") . ' GMT by ' . $this->pluginName . "\n" . 'This page is NOT a cached static HTML file for at least one of a few possible reasons:' . "\n\t" . ' - It is not a WordPress Page/Post' . "\n\t" . ' - It is marked `DONOTCACHEPAGE`' . "\n\t" . ' - It is an Archive (list of Posts) Page' . "\n\t" . ' - You are logged in to this WordPress site' . "\n\t" . ' - It has received HTTP POST data' . "\n\t" . ' - It is a particular WooCommerce page [Cart/Checkout/My Account]' . "\n" . '-->';
				}
			} // END $this->settingMinifyHtml === TRUE
			else
			{
				// minification is switched off
				$html .= "\n" . '<!--' . "\n" . 'Performance enhanced web page generated at ' . gmdate("M d Y H:i:s") . ' GMT by ' . $this->pluginName . "\n" . 'This page could be improved further if HTML minification is enabled in Settings ;)' . "\n" . '-->';
				$this->writeLog('minifyHTML() did nothing because HTML minification is disabled in Settings' );
			}
		    return $html;
		}
		
		public function minifyPost()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'minifyPost() running...' );
			ob_start( array( $this, 'minifyHTML' ) );
		}
		
		/**
		* Wipes the assets cache
		* @return void
		*/
		public function flushAssetCache()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'flushAssetCache() running...' );
			if( is_dir( $this->assetsCache ) )
			{
				$this->recursiveRemoveDirectory( $this->assetsCache );
				$createAssetsCacheDirectory = mkdir( $this->assetsCache, 0755, TRUE );
				if( $createAssetsCacheDirectory === TRUE )
				{
					if( $this->debug === TRUE ) $this->writeLog( 'flushAssetCache() executed with SUCCESS' );
				}
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: flushAssetCache() FAILED, $this->assetsCache NOT created!' );
				}
			}
		} // END flushAssetCache()
		
		/**
		* Wipes the posts cache
		* @return void
		*/
		public function flushPostCache()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'flushPostCache() running...' );
			if( is_dir( $this->postsCache ) )
			{
				$this->recursiveRemoveDirectory( $this->postsCache );
				$createPostsCacheDirectory = mkdir( $this->postsCache, 0755, TRUE );
				if( $createPostsCacheDirectory === TRUE )
				{
					if( $this->debug === TRUE ) $this->writeLog( 'flushPostCache() executed with SUCCESS' );
				}
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: flushPostCache() FAILED, $this->postsCache NOT created!' );
				}
			}
		} // END flushPostCache()
		
		/**
		* Wipes the posts cache
		* @return void
		*/
		public function flushWholeCache()
		{			
			// clear log
			if( file_exists( __DIR__ . '/log.txt' ) )
			{
				unlink( __DIR__ . '/log.txt' );
			}
			if( is_dir( $this->cacheDir ) )
			{
				$this->recursiveRemoveDirectory( $this->cacheDir );
				$createPostsCacheDirectory = mkdir( $this->cacheDir, 0755, TRUE );
				if( $createPostsCacheDirectory === TRUE )
				{
					if( $this->debug === TRUE ) $this->writeLog( 'flushWholeCache() executed with SUCCESS' );
				}
				else
				{
					if( $this->debug === TRUE ) $this->writeLog( 'ERROR: flushWholeCache() FAILED, $this->cacheDir NOT created!' );
				}
			}
		} // END flushPostCache()
		
		public function doAllAssets()
		{
			if( $this->debug === TRUE ) $this->writeLog( 'doAllAssets() running...' );
			$this->doAssets( $this->earlyAssets );
			$this->doAssets( $this->lateAssets );
			if( $this->debug === TRUE ) $this->writeLog( 'doAllAssets() run' );
		}
		
		/**
		* Control function for minifying assets
		* 
		* @return void
		*/
		private function doAssets( array $fileTypes )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'doAssets() running...' );
			global $post;
			if( $this->isViablePost( $post, TRUE ) )
			{
				$flushPostsCache = FALSE;
				foreach( $fileTypes as $fileType )
				{
					$files = $this->getAssets( $fileType );
					if( $this->refreshRequired( $files, $fileType ) === TRUE )
					{
						if( $this->debug === TRUE ) $this->writeLog( 'doAssets() determined refreshRequired() TRUE on file type `'. $fileType .'`' );
						if( $fileType === 'js' )
						{
							if( isset( $_POST['X-WP-Roids'] ) && $_POST['X-WP-Roids'] == TRUE )
							{
								$this->deleteCachePost( $post->ID );
								if( $this->debug === TRUE ) $this->writeLog( 'doAssets() Post ID `' . $post->ID . '` flushed' );
							}
						}
						else
						{
							if( isset( $_POST['X-WP-Roids'] ) && $_POST['X-WP-Roids'] == TRUE )
							{
								$this->flushPostCache();
								if( $this->debug === TRUE ) $this->writeLog( 'doAssets() Post cache flushed' );
							}
						}
						
						$this->refresh( $files, $fileType );
					}
					else
					{
						if( $this->debug === TRUE ) $this->writeLog( 'doAssets() determined refreshRequired() FALSE on file type `'. $fileType .'`' );
					}
					
					$this->requeueAssets( $files, $fileType );
					
				} // END foreach $fileType
				
			} // END if viable post	
			
		} // END doAssets()
		
		/**
		* 
		* @param string $type: Either 'css' or 'js'
		* 
		* @return array $filenames: List of CSS or JS assets. Format: $handle => $src
		*/
		private function getAssets( $type )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'getAssets() running...' );
			$output = array();
			$siteUrl = str_replace( ['https:','http:'], '', $this->siteUrl );
			$path = rtrim( ABSPATH, '/' );
			switch( $type )
			{
				case 'css':
					global $wp_styles;
					$wpAssets = $wp_styles;
					break;
				case 'core-js':
				case 'js':
					global $wp_scripts;
					$wpAssets = $wp_scripts;
					$deps = array();
					break;
			}
			
			foreach( $wpAssets->registered as $wpAsset )
			{
			// nope: core files (apart from 'jquery-core' & 'jquery-migrate'), plugins ignored via Settings, unqueued files & files w/o src
				if( (
					( ( $type === 'css' ) 
						|| ( $type === 'js' 
						&& $this->strposa( $wpAsset->src, $this->settingIgnoredFolders ) === FALSE
						)
					)
					&& ( 
						strpos( $wpAsset->src, 'wp-admin' ) === FALSE
						&& strpos( $wpAsset->src, 'wp-includes' ) === FALSE
						&& ( strpos( $wpAsset->src, $this->domainName ) !== FALSE 
							|| strpos( $wpAsset->src, '/wp' ) === 0 
							|| ( $this->settingCacheCdn === TRUE && strpos( $wpAsset->src, 'cdn' ) !== FALSE && strpos( $wpAsset->src, 'font' ) === FALSE )
							)
						&& strpos( $wpAsset->handle, $this->textDomain ) === FALSE
						&& ( in_array( $wpAsset->handle, $wpAssets->queue ) 
							|| ( isset( $wpAssets->in_footer ) && in_array( $wpAsset->handle, $wpAssets->in_footer ) )
							)
						&& ! empty( $wpAsset->src )
						&& ! is_bool( $wpAsset->src )
						)
					)
					||
					( $type === 'core-js' 
						&& ( $wpAsset->handle === 'jquery-core' || $wpAsset->handle === 'jquery-migrate' )
						&& strpos( $wpAsset->handle, $this->textDomain ) === FALSE
						)
				)
				{
					if( strpos( $wpAsset->src, 'cdn' ) === FALSE )
					{
						// prepend the relational files
						if( ( strpos( $wpAsset->handle, 'jquery' ) === 0 && strpos( $wpAsset->src, $this->domainName ) === FALSE ) || strpos( $wpAsset->src, '/wp' ) === 0 )
						{
							$wpAsset->src = $siteUrl . $wpAsset->src;
						}
						
						// we need the file path for checking file update timestamps later on in refreshRequired()
						$filePath = str_replace( [$this->siteUrl, $siteUrl], $path, $wpAsset->src );
						
						// now rebuild the url from filepath
						$src = str_replace( $path, $this->siteUrl, $filePath );
					}
					else
					{
						if( $this->settingCacheCdn === TRUE )
						{
							// no local filepath as is CDN innit
							$filePath = NULL;
							$src = $wpAsset->src;
						}
					}
					
					// add file to minification array list
					$output[$wpAsset->handle] = array( 'src' => $src, 'filepath' => $filePath, 'deps' => $wpAsset->deps, 'args' => $wpAsset->args, 'extra' => $wpAsset->extra );
					
					// if javascript we need all the dependencies for later in enqueueAssets()
					if( $type === 'js' )
					{
						foreach( $wpAsset->deps as $dep )
						{
							if( ! in_array( $dep, $deps ) ) $deps[] = $dep;
						}						
					}
					
					if( $this->debug === TRUE ) $this->writeLog('type `' . $type . '` getAssets() file: `'.$wpAsset->handle.'` was considered okay to cache/minify');
				} // END if considered ok to minify/cache
				
			} // END foreach registered asset
			
			if( $type === 'js' )
			{
				// set the class property that stores javascript dependencies
				$this->jsDeps = $deps;
				if( $this->debug === TRUE ) $this->writeLog( 'getAssets() $this->jsDeps = ' . print_r( $this->jsDeps, TRUE ) );
			}
			if( $this->settingCacheCdn === FALSE && $this->debug === TRUE ) $this->writeLog( 'getAssets() ignored items from CDNs as option disabled in Settings' );
			if( $this->debug === TRUE ) $this->writeLog( 'getAssets() $output = ' . print_r( $output, TRUE ) );
			return $output;
			
		} // END getAssets()
		
		/**
		* 
		* @param array $filenames: List of CSS or JS assets. Format: $handle => $file
		* @param array $file: Format: $src (http://) => $filepath (directory handle)
		* 
		* @return bool $refresh: Whether we need to recompile our asset file for this type
		*/
		private function refreshRequired( $filenames, $type )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() running...' );
			$refresh = FALSE;
			if( ! is_dir( $this->assetsCache ) ) return TRUE;
			clearstatcache(); // ensures filemtime() is up to date		
			switch( $type )
			{
				case 'css':
					$filenameArray = glob( $this->assetsCache . '/' .  $this->styleFile . "*" );
					break;
				case 'core-js':
					$filenameArray = glob( $this->assetsCache . '/' . $this->coreScriptFile . "*" );
					break;
				case 'js':
					$filenameArray = glob( $this->assetsCache . $this->uri . $this->scriptFile . "*" );
					break;
			}
			
			if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() $filenameArray = "' . print_r( $filenameArray, TRUE ) . '"' );
			
			// there is no plugin generated file, so we must refresh/generate
			if( empty( $filenameArray ) || count( $filenameArray ) !== 1 )
			{
				$refresh = TRUE;
			}			
			// if the plugin generated file exists, we need to check if any inside the $filenames minification array are newer
			else
			{
				$outputFile = $filenameArray[0];
				$editTimes = array();
				$outputFileArray = array( 'filepath' => $outputFile );
				array_push( $filenames, $outputFileArray );
				foreach( $filenames as $file )
				{
					$modified = @filemtime( $file['filepath'] );
					if( $modified === FALSE )
					{
						if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() filemtime FALSE on file `' . $file['filepath'] . '`' );
						$modified = time();
					}
					$editTimes[$modified] = $file;
				}
				krsort( $editTimes );
				if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() $editTimes array = `' . print_r( $editTimes, TRUE ) . '`' );
				$latest = array_shift( $editTimes );
				if( $latest['filepath'] !== $outputFileArray['filepath'] )
				{
					$refresh = TRUE;
					if( file_exists( $outputFile ) ) unlink( $outputFile );
				}
			}
			if( $this->debug === TRUE ) $this->writeLog( 'refreshRequired() returned: ' . $refresh );
			return $refresh;
			
		} // END refreshRequired()
		
		/**
		* 
		* @param array $filenames: List of CSS or JS assets. Format: $handle => $file
		* @param array $file: Format: $src (http://) => $filepath (directory handle)
		* @param string $type: Either 'css' or 'js'
		* 
		* @return bool on success
		*/
		private function refresh( $filenames, $type )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'refresh() running...' );
			$createAssetDirectory = NULL;
			if( ! is_dir( $this->assetsCache ) )
			{
				$createAssetDirectory = mkdir( $this->assetsCache, 0755, TRUE );
				if( $this->debug === TRUE ) $this->writeLog( '$this->assetsCache directory creation attempted' );
			}
			
			if( is_dir( $this->assetsCache ) || $createAssetDirectory === TRUE )
			{
				$output = "<?php\n";
				switch( $type )
				{
					case 'css':
						$output .= "header( 'Content-Type: text/css' );\n";
						$outputFile = $this->assetsCache . '/' . $this->styleFile . $this->timestamp;
						break;
					case 'core-js':
						$output .= "header( 'Content-Type: application/javascript' );\n";
						$outputFile = $this->assetsCache . '/' . $this->coreScriptFile . $this->timestamp;
						break;
					case 'js':
						$output .= "header( 'Content-Type: application/javascript' );\n";
						$outputFile = $this->assetsCache . $this->uri . $this->scriptFile . $this->timestamp;
						if( ! is_dir( $this->assetsCache . $this->uri ) )
						{
							mkdir( $this->assetsCache . $this->uri, 0755, TRUE );
						}
						break;
				} // END switch type	
				$theCode = '';
				foreach( $filenames as $handle => $file )
				{
					if( $file['filepath'] !== NULL )
					{
						$fileDirectory = dirname( $file['filepath'] );
						$fileDirectory = realpath( $fileDirectory );
					}	
		        	$contentDir = $this->rootDir;
		        	$contentUrl = $this->siteUrl;
					// cURL b/c if CSS dynamically generated w. PHP, file_get_contents( $file['filepath'] ) will return code, not CSS
					// AND using file_get_contents( $file['src'] ) will return 403 unauthourised
			        $curlOptions = array(
			            CURLOPT_URL => $file['src'],
			            CURLOPT_REFERER => $file['src'],
			            CURLOPT_HEADER => FALSE,
			            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
			            CURLOPT_RETURNTRANSFER => TRUE,
			        );
					$ch = curl_init();
	        		curl_setopt_array( $ch, $curlOptions );
	        		$code = curl_exec( $ch );
	        		curl_close( $ch );
	        		
	        		// is there code? do stuff
	        		if( strlen( $code ) !== 0 && ! empty( $code ) )
	        		{
	        			// if conditional e.g. IE CSS get rid of it, let WP do it's thing
	        			if( $type === 'css' && ! empty( $file['extra']['conditional'] ) )
	        			{
							unset( $filenames[$handle] );
							break;
						}			
						
						// if inline CSS stuff included, add to code
						if( $type === 'css' && ! empty( $file['extra']['after'] ) )
						{
							$code .= "\n" . $file['extra']['after'][0];
						}
						     			 		
		        		// CSS with relative background-image(s) but NOT "data:" / fonts set etc., convert them to absolute
		        		if( $type === 'css' && strpos( $code, 'url' ) !== FALSE && $file['filepath'] !== NULL )
		        		{
						    $code = preg_replace_callback(
						        '~url\(\s*(?![\'"]?data:)\/?(.+?)[\'"]?\s*\)~i',
						        function( $matches ) use ( $fileDirectory, $contentDir, $contentUrl )
						        {
						        	$filePath = $fileDirectory . '/' . str_replace( ['"', "'"], '', ltrim( rtrim( $matches[0], ');' ), 'url(' ) );
						        	return "url('" . esc_url( str_replace( $contentDir, $contentUrl, $filePath ) ) . "')";
						        },
						        $code
						    );
						} // END relative -> absolute
						
						// if a CSS media query file, wrap in width params
						if( $type === 'css' && strpos( $file['args'], 'width' ) !== FALSE )
						{
							$code = '@media ' . $file['args'] . ' { ' . $code . ' } ';
						}
						
						// fix URLs with // prefix so not treated as comments
						$code = str_replace( ['href="//','src="//','movie="//'], ['href="http://','src="http://','movie="http://'], $code );
						
						// braces & brackets
						$bracesBracketsLookup = [' {', ' }', '{ ', '; ', "( '", "' )", ' = ', '{ $', '{ var'];
						$bracesBracketsReplace = ['{', '}', '{', ';', "('", "')", '=', '{$', '{var'];
						
						if( $type === 'css' )
						{
							// regex adapted from: http://stackoverflow.com/q/9329552 
							$comments = '~\/\*[^*]*\*+([^/*][^*]*\*+)*\/~';
							$replace = NULL;
						}
						
						if( $type === 'js' || $type === 'core-js' )
						{
							// regex adapted from: http://stackoverflow.com/a/31907095
							// added rule for only two "//" to avoid stripping base64 lines
							// added rule for optional whitespace after "//" as some peeps do not space
							$comments = '~(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\"|\/)\/\/(?!\/+)\s?.*))~';
							$replace = NULL;
						}
						
						// strip comments
						$code = preg_replace( $comments, $replace, $code );
						
						// strip spaces in braces
						$code = str_replace( $bracesBracketsLookup, $bracesBracketsReplace, $code );
												
						// strip excessive newlines
						$code = preg_replace( '/\r/', "\n", $code );
						$code = preg_replace( '/\n+/', "\n", $code );
						
						// strip whitespace
						$code = preg_replace( '/\s+/', ' ', $code );
						
						// hacky fix for missing semicolons
						if( $type === 'js' )
						{
							if( substr( trim( $code ), -8, 8 ) === '(jQuery)' )
							{
								$code .= ';';
							}
						}
							
						$code = ltrim( $code, "\n" );
						
						$theCode .= $code;
						
						unset( $filenames[$handle] );
						
					} // END if code
					
				} // END foreach $filenames
						
				if( $type === 'css' && strpos( $theCode, '@charset "UTF-8";' ) !== FALSE )
				{
					$theCode = '@charset "UTF-8";' . "\n" . str_replace( '@charset "UTF-8";', '', $theCode );
				}
				
				$output .= "header( 'Last-Modified: ".gmdate( 'D, d M Y H:i:s' )." GMT' );\nheader( 'Expires: ".gmdate( 'D, d M Y H:i:s', strtotime( '+1 year' ) )." GMT' );\n?>\n";
				
				$outputFile .= '.php';
				$fh = fopen( $outputFile, 'wb' );
				fwrite( $fh, $output . $theCode );
				fclose( $fh );
				if( $this->debug === TRUE ) $this->writeLog( 'Asset `' . $outputFile . '` written' );
				return $filenames;
			}
			else
			{				
				if( $this->debug === TRUE ) $this->writeLog( 'ERROR: $this->assetsCache directory does NOT exist - possible permissions issue' );
				return FALSE;
			}
			
		} // END refresh()
		
		/**
		* Dequeues all the assets we are replacing
		* @param array $filenames: List of CSS or JS assets. Format: $handle => $file
		* @param array $file: Format: $src (http://) => $filepath (directory handle)
		* @param string $type: Either 'css' or 'js'
		* 
		* @return bool on success
		*/
		private function requeueAssets( $filenames, $type )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'requeueAssets() running...' );
			switch( $type )
			{
				case 'css':
					foreach( $filenames as $handle => $file )
					{
						wp_dequeue_style( $handle );
						if( strpos( $handle, $this->textDomain ) === FALSE )
						{
							wp_deregister_style( $handle );
							if( $this->debug === TRUE ) $this->writeLog( 'CSS deregistered = `' . $handle . '`' );
						}
					}
					$styles = glob( $this->assetsCache . '/' . $this->styleFile . "*" );
					$styles = ltrim( str_replace( $this->rootDir, '', $styles[0] ), '/' );
					$styles = str_replace( '.php', '.css', $styles );
					wp_enqueue_style( $this->textDomain . '-styles', esc_url( site_url( $styles ) ), array(), NULL );
					if( $this->debug === TRUE ) $this->writeLog( 'CSS enqueued = `' . site_url( $styles ) . '`' );
					break;
				case 'core-js':
					foreach( $filenames as $handle => $file )
					{
						wp_deregister_script( $handle );
						if( $this->debug === TRUE ) $this->writeLog( 'Old core JS dequeued = `' . $handle . '`' );
					}
					$coreScripts = glob( $this->assetsCache . '/' . $this->coreScriptFile . "*" );
					$coreScripts = ltrim( str_replace( $this->rootDir, '', $coreScripts[0] ), '/' );
					$coreScripts = str_replace( '.php', '.js', $coreScripts );
					wp_enqueue_script( $this->textDomain . '-core', esc_url( site_url( $coreScripts ) ), array(), NULL );
					wp_deregister_script( 'jquery' );
					wp_deregister_script( 'jquery-migrate' );
					wp_register_script( 'jquery', '', array( $this->textDomain . '-core' ), NULL, TRUE );
					wp_enqueue_script( 'jquery' );
					if( $this->debug === TRUE ) $this->writeLog( 'New core JS enqueued' );
					break;
				case 'js':
					$inlineJs = '';
					foreach( $filenames as $handle => $file )
					{
						// check for inline data
						if( ! empty( $file['extra']['data'] ) )
						{
							$inlineJs .= $file['extra']['data'] . "\n";
						}
						
						if( strpos( $handle, $this->textDomain ) === FALSE )
						{
							wp_dequeue_script( $handle );
							if( $this->debug === TRUE ) $this->writeLog( 'JS script dequeued = `' . $handle . '`' );
						}
					}
					$scripts = glob( $this->assetsCache . $this->uri . $this->scriptFile . "*" );
					$scripts = ltrim( str_replace( $this->rootDir, '', $scripts[0] ), '/' );
					$scripts = str_replace( '.php', '.js', $scripts );
					$scriptsAdded = wp_register_script( $this->textDomain . '-scripties', esc_url( site_url( $scripts ) ), $this->jsDeps, NULL, TRUE );
					if( $scriptsAdded === TRUE )
					{
						wp_enqueue_script( $this->textDomain . '-scripties' );
						if( $this->debug === TRUE ) $this->writeLog( $this->textDomain . '-scripties wp_register_script SUCCESS' );
					}
					else
					{
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: ' . $this->textDomain . '-scripties wp_register_script FAILED!' );
					}
					if( $inlineJs !== '' )
					{
						// strip excessive newlines
						$inlineJs = preg_replace( '/\r/', "\n", $inlineJs );
						$inlineJs = preg_replace( '/\n+/', "\n", $inlineJs );
					
						// strip whitespace
						$inlineJs = preg_replace( '/\s+/', ' ', $inlineJs );
										
						$inlineAdded = wp_add_inline_script( $this->textDomain . '-scripties', $inlineJs, 'before' );
						if( $inlineAdded === TRUE )
						{
							if( $this->debug === TRUE ) $this->writeLog( 'Inline script added = `' . $inlineJs . '`' );
						}
						else
						{
							if( $this->debug === TRUE ) $this->writeLog( 'ERROR: Inline script add FAILED! = `' . $inlineJs . '`' );
						}
						
					}
					if( $this->debug === TRUE ) $this->writeLog( 'JS script enqueued = `' . $this->textDomain . '-scripties` with deps = `' . print_r( $this->jsDeps, TRUE ) . '`' );
					break;
					
			} // END switch type
			
			return TRUE;
			
		} // END requeueAssets()
		
		/**
		* Removes query strings from asset URLs
		* @param string $src: the src of an asset file
		* 
		* @return string: the src with version query var removed
		*/
		public function removeScriptVersion( $src )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'removeScriptVersion() running on src `' . $src . '`' );
			$parts = explode( '?ver', $src );
			return $parts[0];
			
		} // END removeScriptVersion()
		
		/**
		* Deletes a directory and its contents
		* @param string $directory: directory to empty
		* 
		* @return void
		*/
		private function recursiveRemoveDirectory( $directory )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'recursiveRemoveDirectory() running on `' . $directory . '`' );
		    if( ! is_dir( $directory ) )
		    {
		        if( $this->debug === TRUE ) $this->writeLog( 'ERROR: recursiveRemoveDirectory() directory `' . $directory . '` does NOT exist!' );
		        exit;
		    }
		    
		    if( substr( $directory, strlen( $directory ) - 1, 1 ) != '/' )
		    {
		        $directory .= '/';
		    }
		    
		    $files = glob( $directory . "*" );
		    
		    if( ! empty( $files ) )
		    {
			    foreach( $files as $file )
			    {
			        if( is_dir( $file ) )
			        {
			            $this->recursiveRemoveDirectory( $file );
			        }
			        else
			        {
			            unlink( $file );
			        }
			    }				
			}
		    if( is_dir( $directory ) ) rmdir( $directory );
		    
		} // END recursiveRemoveDirectory()
		
		/**
		* Deletes a directory and its contents
		* @param string $directory: directory to empty
		* 
		* @return void
		*/
		private function recursiveRemoveEmptyDirectory( $directory )
		{
			if( $this->debug === TRUE ) $this->writeLog( 'recursiveRemoveEmptyDirectory() running on `' . $directory . '`' );
		    if( ! is_dir( $directory ) )
		    {
		        if( $this->debug === TRUE ) $this->writeLog( 'ERROR: recursiveRemoveEmptyDirectory() directory `' . $directory . '` does NOT exist!' );
		        exit;
		    }
		    
		    if( substr( $directory, strlen( $directory ) - 1, 1 ) != '/' )
		    {
		        $directory .= '/';
		    }
		    
		    $files = glob( $directory . "*" );
		    
		    if( ! empty( $files ) )
		    {
			    foreach( $files as $file )
			    {
			        if( is_dir( $file ) )
			        {
			            $this->recursiveRemoveEmptyDirectory( $file );
			        }
			    }				
			}
			else
			{
				if( is_dir( $directory ) ) rmdir( $directory );
			}		    
		    
		} // END recursiveRemoveEmptyDirectory()
		
		/**
		* Display a message
		*/
		private function notice( $message, $type = 'error' )
		{
			switch( $type )
			{
				case 'error':
					$glyph = 'thumbs-down';
					$color = '#dc3232';
					break;
				case 'updated':
					$glyph = 'thumbs-up';
					$color = '#46b450';
					break;
				case 'warning':
					$glyph = 'megaphone';
					$color = '#ff7300';
					break;
			}
			$output = '<div id="message" class="notice is-dismissible '.$type.'"><p><span style="color: '.$color.';" class="dashicons dashicons-'.$glyph.'"></span>&nbsp;&nbsp;&nbsp;<strong>';
			$output .= __( $message , $this->textDomain );
			if( $type === 'error' )
			{
				$output .= '</strong></p><p><strong>Ignore the message below that the Plugin is active, it isn\'t!';
			}
			$output .= '</strong></p></div>';
			return $output;
        } // END notice()
        
        public function messageCurlRequired()
        {
        	$message = 'Sorry, '.$this->pluginName.' requires the cURL PHP extension installed on your server. Please resolve this';
			echo $this->notice( $message );
		} // END messageCurlRequired()
        
        public function messageHtNotWritable()
        {
        	$message = 'Sorry, ' . $this->pluginName . ' requires ".htaccess" to be writable to activate/deactivate. Some security plugins disable this. Please allow the file to be writable, for a moment. You can re-apply your security settings after activating/deactivating ' . $this->pluginName;
			echo $this->notice( $message );
		} // END messageHtNotWritable()
        
        public function messageCachingDetected()
        {
        	$message = 'Sorry, ' . $this->pluginName . ' requires no other caching/minification Plugins be active. Please deactivate any existing Plugin(s) of this nature';
			echo $this->notice( $message );
		} // END messageCachingDetected()
        
        public function messageConflictDetected()
        {
        	$message = 'Sorry, ' . $this->pluginName . ' does NOT work with the following Plugins due to bad/intrusive coding practices on their part. Please deactivate these Plugins if you wish to use ' . $this->pluginName;
        	$message .= '<ul>';
        	foreach( $this->conflictingPlugins as $conflictingPlugin )
        	{
				$message .= '<li>' . $conflictingPlugin['name'] . ' ~ <small>[source: <a href="' . $conflictingPlugin['ref'] . '" target="_blank" title="Opens in new window">' . $conflictingPlugin['ref'] . '</a>]</small></li>';
			}
        	$message .= '</ul>';
			echo $this->notice( $message );
		} // END messageConflictDetected()
        
        public function messageCacheFlushed()
        {
        	$message = 'Groovy! ' . $this->pluginName . ' cache has been flushed';
			echo $this->notice( $message, 'updated' );
		} // END messageCacheFlushed()
        
        public function messageSettingsSaved()
        {
        	$message = 'Awesome! ' . $this->pluginName . ' setting have been saved!';
			echo $this->notice( $message, 'updated' );
		} // END messageSettingsSaved()
		
		/**
		* Add links below plugin description
		* @param array $links: The array having default links for the plugin
		* @param string $file: The name of the plugin file
		* 
		* @return array $links: The new links array
		*/
		public function pluginMetaLinks( $links, $file )
		{
			if ( $file == plugin_basename( dirname( __FILE__ ) . '/wp-roids.php' ) )
			{
				$links[] = '<a href="https://philmeadows.com/say-thank-you/" target="_blank" title="Opens in new window">' . __( 'Say "Thank You"', $this->textDomain ) . '</a>';
			}
			return $links;
			
		} // END pluginMetaLinks()
		
		/**
		* Add links when viewing "Plugins"
		* @param array $links: The links that appear by "Deactivate" under the plugin name
		* 
		* @return array $links: Our new set of links
		*/
		public function pluginActionLinks( $links )
		{
			$mylinks = array(
				'<a href="' . esc_url( admin_url( 'edit.php?page=' . $this->textDomain ) ) . '">Settings</a>',
				$this->flushCacheLink(),
				);
			return array_merge( $links, $mylinks );
			
		} // END pluginActionLinks()
		
		/**
		* Generates a clickable "Flush Cache" link
		* 
		* @return string HTML
		*/
		private function flushCacheLink( $linkText = 'Flush Cache' )
		{
			$url = admin_url( 'admin.php?page=' . $this->textDomain );
			$link = wp_nonce_url( $url, $this->nonceAction, $this->nonceName );
			return sprintf( '<a class="flush-link" href="%1$s">%2$s</a>', esc_url( $link ), $linkText );
			
		} // END flushCacheLink()
		
		/**
		* Add "Flush Cache" link to Admin Bar
		* @param object $adminBar
		* 
		* @return void
		*/
		public function adminBarLinks( $adminBar )
		{
			if( current_user_can( 'install_plugins' ) )
			{
				$url = admin_url( 'admin.php?page=' . $this->textDomain );
				$link = wp_nonce_url( $url, $this->nonceAction, $this->nonceName );
				$adminBar->add_menu(
					[ 'id' => $this->textDomain . '-flush',
					'title' => 'Flush ' . $this->pluginName . ' Cache',
					'href'  => esc_url( $link ),
					] );
			}
			
		} // END adminBarLinks()
		
		/**
		* Add Credit Link to footer if enabled
		* 
		* @return void
		*/
		public function creditLink()
		{
			if( $this->settingCreditLink === TRUE )
			{
				echo '<p id="' . $this->textDomain . '-credit" style="clear:both;float:right;margin:0.5rem 1.75rem;font-size:11px;position:relative;transform:translateY(-250%);z-index:50000;"><a href="https://wordpress.org/plugins/wp-roids/" target="_blank" title="' . $this->pluginStrapline . ' | Opens in new tab/window">Performance enhanced by ' . $this->pluginName . '</a></p><div style="clear:both;"></div>';
			}
			
		} // END creditLink()
		
		/**
		* Called on plugin deactivation - cleans everything up as if we were never here :)
		* @return void
		*/
		public function deactivate()
		{			
			// .htaccess needs to be writable, some security plugins disable this
			// only perform this check when an admin is logged in, or it'll deactivate the plugin :/
			if( current_user_can( 'install_plugins' ) )
			{		
				$htaccess = $this->rootDir . '/.htaccess';
				$current = file_get_contents( $htaccess );
				$starttext = '### BEGIN WP Roids - DO NOT REMOVE THIS LINE';
				$endtext = '### END WP Roids - DO NOT REMOVE THIS LINE' . "\n\n";
				if( strpos( $current, $starttext ) !== FALSE && strpos( $current, $endtext ) !== FALSE )
				{
					// .htaccess needs editing
					$desiredPerms = fileperms( $htaccess );
					chmod( $htaccess, 0644 );
					$fh = fopen( $htaccess, 'wb' );
					$isHtaccessWritable = fwrite( $fh, $current );
					fclose( $fh );
					if( $isHtaccessWritable === FALSE )
					{				
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: function deactivate() `.htaccess` NOT writable!' );
						add_action( 'admin_notices', array( $this, 'messageHtNotWritable' ) );
						return FALSE;
					}
					else
					{
						// restore the .htaccess file
						$pos = strpos( $current, $starttext );
						$startpoint = $pos === FALSE ? NULL : $pos;
						$pos = strrpos( $current, $endtext, $startpoint );
						$endpoint = $pos === FALSE ? NULL : $pos + strlen( $endtext );
						if( $startpoint !== NULL && $endpoint !== NULL )
						{
							$restore = substr_replace( $current, '', $startpoint, $endpoint - $startpoint);
							$fh = fopen( $htaccess, 'wb' );
							fwrite( $fh, $restore );
							fclose( $fh );
							chmod( $htaccess, $desiredPerms );
						}
					}
				} // END .htaccess needs editing
				
				// remove 1.* versions' code
				$current = file_get_contents( $htaccess );
				$starttext = '# BEGIN WP Roids - DO NOT REMOVE THIS LINE';
				$endtext = '# END WP Roids - DO NOT REMOVE THIS LINE' . "\n\n";
				if( strpos( $current, $starttext ) !== FALSE && strpos( $current, $endtext ) !== FALSE )
				{
					// .htaccess needs editing
					$desiredPerms = fileperms( $htaccess );
					chmod( $htaccess, 0644 );
					$fh = fopen( $htaccess, 'wb' );
					$isHtaccessWritable = fwrite( $fh, $current );
					fclose( $fh );
					if( $isHtaccessWritable === FALSE )
					{				
						if( $this->debug === TRUE ) $this->writeLog( 'ERROR: function deactivate() `.htaccess` NOT writable!' );
						add_action( 'admin_notices', array( $this, 'messageHtNotWritable' ) );
						return FALSE;
					}
					else
					{
						// restore the .htaccess file
						$pos = strpos( $current, $starttext );
						$startpoint = $pos === FALSE ? NULL : $pos;
						$pos = strrpos( $current, $endtext, $startpoint );
						$endpoint = $pos === FALSE ? NULL : $pos + strlen( $endtext );
						if( $startpoint !== NULL && $endpoint !== NULL )
						{
							$restore = substr_replace( $current, '', $startpoint, $endpoint - $startpoint);
							$fh = fopen( $htaccess, 'wb' );
							fwrite( $fh, $restore );
							fclose( $fh );
							chmod( $htaccess, $desiredPerms );
						}
					}
				} // END .htaccess needs editing
					
				$backup = __DIR__ . '/ht-backup.txt';
				if( file_exists( $backup ) )
				{
					unlink( $backup );
				}
				
				$log = __DIR__ . '/log.txt';
				if( file_exists( $log ) )
				{
					unlink( $log );
				}
				
				// remove cache
				if( is_dir( $this->cacheDir ) ) $this->recursiveRemoveDirectory( $this->cacheDir );
				
				// kill the schedule
				$scheduleTimestamp = wp_next_scheduled( $this->textDomain . '_flush_schedule' );
				if( $scheduleTimestamp !== FALSE )
				{
					wp_unschedule_event( $scheduleTimestamp, $this->textDomain . '_flush_schedule' );
				}
			
			} // END if user can activate plugins	
			
		} // END deactivate()
		
		/**
		* Called on uninstall - actually does nothing at present
		* @return void
		*/
		public static function uninstall()
		{
			global $wpdb;
			$theClass = self::instance();
			$theClass->deactivate();				
			// delete plugin options
			$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'options WHERE option_name LIKE \'' . $theClass->textDomain . '%\'' );			
			
		} // END uninstall()
		
		/**
		* Create or return instance of this class
		*/
		public static function instance()
		{
			$className = get_class();
			if( ! isset( self::$instance ) && ! ( self::$instance instanceof $className ) && ( self::$instance === NULL ) )
			{
				self::$instance = new $className;
			}
			return self::$instance;
			
		} // END instance()
		
		/**
		* load admin scripts
		*/
		public function loadAdminScripts()
		{
			wp_enqueue_style( $this->textDomain.'-admin-webfonts', 'https://fonts.googleapis.com/css?family=Roboto:400,700|Roboto+Condensed', array(), NULL );
			wp_enqueue_style( $this->textDomain.'-admin-styles', plugins_url( 'css-admin.css' , __FILE__ ), array(), NULL );
			
		} // END loadAdminScripts()
		
		/**
		* add admin menu
		*/
		public function adminMenu()
		{
			// see https://developer.wordpress.org/reference/functions/add_menu_page
			add_menu_page( $this->pluginName, $this->pluginName, 'install_plugins', $this->textDomain, array( $this, 'adminPage' ), 'dashicons-dashboard', '80.01' );
			
		} // END adminMenu()
		
		/**
		* our admin page
		*/
		public function adminPage()
		{
			if( isset( $_REQUEST[$this->nonceName] ) && wp_verify_nonce( $_REQUEST[$this->nonceName], $this->nonceAction ) )
			{
				if( $_POST )
				{
					// settings form submitted
					update_option( $this->textDomain.'_settings', NULL );
					$settings = array();
					foreach( $_POST as $key => $setting )
					{
						if( $setting === 'true' )
						{
							$settings[$key] = array( 'disabled' => TRUE );
							if( $key === 'imgs' && is_dir( $this->imgCache ) )
							{
								$this->recursiveRemoveDirectory( $this->imgCache );
							}
						}
						elseif( $key === 'imgs-quality-jpeg' || $key === 'imgs-quality-png' || $key === 'schedule' || $key === 'debug' || $key === 'credit' )
						{
							$settings[$key] = array( 'value' => $setting );
							if( $key === 'imgs-quality-jpeg' && is_numeric( $setting ) )
							{
								$this->compressionLevelJpeg = intval( $setting );
							}
							if( $key === 'imgs-quality-png' && is_numeric( $setting ) )
							{
								$this->compressionLevelPng = intval( $setting );
							}
							if( $key === 'debug' && $setting === 'disabled' )
							{
								$this->debug = FALSE;
								$log = __DIR__ . '/log.txt';
								if( file_exists( $log ) )
								{
									unlink( $log );
								}
							}
							if( $key === 'debug' && $setting === 'enabled' )
							{
								$this->debug = TRUE;
							}
							if( $key === 'schedule' && $setting === 'disabled' )
							{
								$this->settingFlushSchedule = FALSE;
								// kill the schedule
								$scheduleTimestamp = wp_next_scheduled( $this->textDomain . '_flush_schedule' );
								if( $scheduleTimestamp !== FALSE )
								{
									wp_unschedule_event( $scheduleTimestamp, $this->textDomain . '_flush_schedule' );
									if( $this->debug === TRUE ) $this->writeLog( 'CRON schedule killed!' );
								}
							}
							if( $key === 'schedule' && $setting !== 'disabled' )
							{					
								// set event to flush posts
								$this->settingFlushSchedule = $setting;
								// kill the schedule
								$scheduleTimestamp = wp_next_scheduled( $this->textDomain . '_flush_schedule' );
								if( $scheduleTimestamp !== FALSE )
								{
									wp_unschedule_event( $scheduleTimestamp, $this->textDomain . '_flush_schedule' );
								}
								if( ! wp_next_scheduled( $this->textDomain . '_flush_schedule' ) )
								{
								    wp_schedule_event( time(), $this->settingFlushSchedule, $this->textDomain . '_flush_schedule' );
									if( $this->debug === TRUE ) $this->writeLog( 'CRON schedule set!' );
								}				
							}
							if( $key === 'credit' && $setting === 'enabled' )
							{
								$this->settingCreditLink = TRUE;
							}
						}
					}
					update_option( $this->textDomain.'_settings', $settings );
					if( $this->debug === TRUE ) $this->writeLog( 'Settings updated!' );				
				}
				
				$this->flushWholeCache();
			}			
			$this->settings = get_option( $this->textDomain.'_settings', NULL );
			if( isset( $_REQUEST[$this->nonceName] ) && wp_verify_nonce( $_REQUEST[$this->nonceName], $this->nonceAction ) )
			{
				
				$this->messageCacheFlushed();
				if( $_POST ) $this->messageSettingsSaved();
			}	
			?>
			<div class="wrap">
				<p class="right">					
					<?php
					if( ! isset( $_REQUEST[$this->nonceName] ) )
					{
						echo $this->flushCacheLink( 'Empty the cache!' );
					}					
					?>
				</p>
				<h1><span class="dashicons dashicons-dashboard"></span>&nbsp;<?php echo $this->pluginName ;?></h1>
				<div class="clear"></div>
				<h4><?php echo $this->pluginStrapline; ?></h4>
				<p class="like">&hearts; <small>Like this plugin?&nbsp;&nbsp;&nbsp;</small><a href="https://philmeadows.com/say-thank-you" target="_blank" title="Opens in new tab/window">Say &quot;Thanks&quot;</a>&nbsp;&nbsp;<a href="https://wordpress.org/support/plugin/wp-roids/reviews/" target="_blank" title="Opens in new tab/window">&#9733;&#9733;&#9733;&#9733;&#9733; Review</a></p>
				<ul class="cssTabs">
					<li>
						<input id="tab-1" type="radio" name="tabs" checked="checked">
						<label for="tab-1">Overview</label>
						<div>
							<div class="fadey">
								<h2>Instructions</h2>
								<p><big><strong><?php echo $this->pluginName; ?> <em>should</em> work out of the box</strong>, the intention being to, &quot;Keep It Simple, Stupid&quot; <abbr>(KISS)</abbr></big> <sup>[<a href="https://en.wikipedia.org/wiki/KISS_principle" target="_blank" title="&quot;Keep It Simple, Stupid&quot; | Opens in new tab/window">?</a>]</sup></p>
								<p>If you <em>want</em> to tinker/debug, go to the &quot;Settings&quot; tab.</p>
								<h3>To Check <?php echo $this->pluginName; ?> Is Working</h3>
								<ul>
									<li>View the source code <sup>[<a href="http://www.computerhope.com/issues/ch000746.htm" target="_blank" title="How to view your website source code | Opens in new tab/window">?</a>]</sup> of a Page/Post <strong>when you are logged out of WordPress<sup>&reg;</sup> and have refreshed the Page/Post TWICE</strong></li>
									<li>At the very bottom, <strong>you should see an HTML comment</strong> like this: <code>&lt;!-- Static HTML cache file generated at <?php echo date( 'M d Y H:i:s T' ); ?> by <?php echo $this->pluginName; ?> plugin --&gt;</code></li>
								</ul>
							</div>
							<div class="pkm-panel pkm-panel-primary fadey">
								<h2>Polite Request&#40;s&#41;&hellip;</h2>
								<p><big>I've made <?php echo $this->pluginName; ?> available <strong>completely FREE of charge</strong>. No extra costs for upgrades, support etc. <strong>It's ALL FREE!</strong></big><br>It takes me a LOT of time to code, test, re-code etc. Time which I am not paid for. To that end, I kindly ask the following from you guys:</p>
								<ul>
									<li>
										<h3>Non-Profit / Non-Commercial Users</h3>
										<p><big>Please consider <a href="https://wordpress.org/support/plugin/wp-roids/reviews/" target="_blank" title="Opens in new tab/window">giving <?php echo $this->pluginName; ?> a 5 Star &#9733;&#9733;&#9733;&#9733;&#9733; Review</a> to boost its popularity</big></p>
									</li>
									<li>
										<h3>Business / Commercial Website Owners</h3>
										<p><big>As above, but a small cash donation via my <a href="https://philmeadows.com/say-thank-you" target="_blank" title="Opens in new tab/window">&quot;Thank You&quot; Page</a> would also be gratefully appreciated</big></p>
									</li>
									<li>
										<h3>WordPress<sup>&reg;</sup> Developers</h3>
										<p><big>Again, as above. However, I would LOVE a (suggested) donation of &#36;39 USD</big><br>
										You can always bill it to your client! ;)
										</p>
									</li>
									<li>
										<h3>Everybody</h3>
										<p><big>Finally, at the bottom of the <label for="tab-2">Settings tab</label>, there is an option to add a small, unintrusive link at the bottom right of your website to the <?php echo $this->pluginName; ?> home page at the WordPress<sup>&reg;</sup> Plugin Repository. I would really appreciate you enable this &#40;if it looks okay&#41;</big></p>
									</li>
								</ul>
								<p><big>Thanks for your time and support!</big></p>
								<p><big>Phil :)</big></p>
								<p style="text-align: center;">
									<a class="gratitude" href="https://philmeadows.com/say-thank-you" target="_blank" title="Opens in new tab/window">Say &quot;Thanks&quot;</a>
									<a class="gratitude" href="https://wordpress.org/support/plugin/wp-roids/reviews/" target="_blank" title="Opens in new tab/window">&#9733;&#9733;&#9733;&#9733;&#9733; Review</a>
								</p>
							</div>
						</div>
					</li>
					<li>
						<input id="tab-2" type="radio" name="tabs">
						<label for="tab-2">Settings</label>
						<div>
							<form action="" method="POST" id="<?php echo $this->textDomain.'-form';?>">
								<input style="display: none;" type="checkbox" name="scroll-hack" value="null" checked>
								<div class="pkm-panel pkm-panel-primary-alt fadey">
									<h2>Core Settings</h2>
									<p>
										<big>Inject <?php echo $this->pluginName; ?> to do the following:</big><br>
										<small>Ideally, leave these all green. If there <em>is</em> a Plugin conflict &mdash; in the first instance, try disabling JS optimisation for that Plugin in the &quot;Plugins JavaScript&quot; section below, <em>before</em> disabling any of these options</small>
									</p>
									<div>
									<input type="checkbox" id="cache" name="cache" value="true"<?php echo( intval( $this->settings['cache']['disabled'] ) === 1 ? ' checked' : ''); ?>>
									<label for="cache">Generate cached <sup>[<a href="https://www.maxcdn.com/one/visual-glossary/web-cache/" target="_blank" title="What is a Web Cache? | Opens in new tab/window">1</a>,<a href="https://en.wikipedia.org/wiki/Web_cache" target="_blank" title="Web cache [Wikipedia] | Opens in new tab/window">2</a>]</sup> static <abbr title="HyperText Markup Language">HTML</abbr> files</label>
									</div>
									<div>
									<input type="checkbox" id="imgs" name="imgs" value="true"<?php echo( intval( $this->settings['imgs']['disabled'] ) === 1 ? ' checked' : ''); ?>>
									<label for="imgs">
										Compress images loaded via <abbr title="HyperText Markup Language">HTML</abbr> <small>&#40;not <abbr title="Cascading Style Sheets">CSS</abbr> <code>background</code> images&#41;</small>
									</label>
										<div class="sub-options">
											<h5>JPEG Image Quality</h5>
											<table cellpadding="0" cellspacing="0" border="0">
												<tr>
													<td width="30%"><small>Lower quality, faster loading</small></td>
													<td width="40%"><input type="range" id="imgs-quality-jpeg" name="imgs-quality-jpeg" min="10" max="80" value="<?php echo intval( $this->compressionLevelJpeg ); ?>" step="5"></td>
													<td width="30%"><small>Higher quality, slower loading</small></td>
												</tr>
											</table>
											<h5>PNG Image Quality</h5>
											<table cellpadding="0" cellspacing="0" border="0">
												<tr>
													<td width="30%"><small>Lower quality, faster loading</small></td>
													<td width="40%"><input type="range" id="imgs-quality-png" name="imgs-quality-png" min="10" max="80" value="<?php echo intval( $this->compressionLevelPng ); ?>" step="5"></td>
													<td width="30%"><small>Higher quality, slower loading</small></td>
												</tr>
											</table>
										</div>
									</div>
									<div>
									<input type="checkbox" id="html" name="html" value="true"<?php echo( intval( $this->settings['html']['disabled'] ) === 1 ? ' checked' : ''); ?>>
									<label for="html">Minify <abbr title="HyperText Markup Language">HTML</abbr></label>
									</div>
									<div>
									<input type="checkbox" id="defer" name="defer" value="true"<?php echo( intval( $this->settings['defer']['disabled'] ) === 1 ? ' checked' : ''); ?>>
									<label for="defer">Defer loading of the <?php echo $this->pluginName; ?> generated minified <abbr title="JavaScript">JS</abbr> file</label>
									</div>
									<div>
									<input type="checkbox" id="theme" name="theme" value="true"<?php echo( intval( $this->settings['theme']['disabled'] ) === 1 ? ' checked' : ''); ?>>
									<label for="theme">Minify and collate <abbr title="Cascading Style Sheets">CSS</abbr> &amp; <abbr title="JavaScript">JS</abbr> in current Theme &quot;<?php echo $this->theme->Name; ?>&quot;</label>
									</div>
									<div>
									<input type="checkbox" id="cdn" name="cdn" value="true"<?php echo( intval( $this->settings['cdn']['disabled'] ) === 1 ? ' checked' : ''); ?>>
									<label for="cdn">Minify and collate <abbr title="Cascading Style Sheets">CSS</abbr> &amp; <abbr title="JavaScript">JS</abbr> loaded via <abbr title="Content Delivery Network">CDN</abbr></label>
									</div>
									<?php submit_button(); ?>
								</div>
							
								<div class="pkm-panel pkm-panel-primary-alt fadey">
									<h2>Plugins JavaScript</h2>
									<p>
										<big>Click on Plugin names to toggle optimisation any queued <abbr title="JavaScript">JS</abbr> assets.</big><br>
										<small>&#40;Useful for debugging&#41;</small>
									</p>							
									<?php
									$allPlugins = get_plugins();
									$ignores = array(
										$this->pluginName,
									);
									foreach( $allPlugins as $pluginFile => $pluginInfo )
									{
										$allPlugins[$pluginFile]['isActive'] = is_plugin_active( $pluginFile ) ? 'true' : 'false';
										$pluginKey = array_search( $pluginFile, array_column( $this->cachingPlugins, 'slug' ) );
										if( ( $pluginKey !== FALSE && is_numeric( $pluginKey ) ) || in_array( $pluginInfo['Name'], $ignores ) )
										{
											unset( $allPlugins[$pluginFile] );
										}
										
									}
									foreach( $allPlugins as $pluginFile => $pluginInfo )
									{
										$pluginFileKey = str_replace( '.', '_', $pluginFile );
										if( $this->settings !== NULL && isset( $this->settings[$pluginFileKey] ) )
										{
											$pluginSettings = $this->settings[$pluginFileKey];
										}
										else
										{
											$pluginSettings = array(
												'disabled' => FALSE,
											);
										}
										?>
										<div>
										<input type="checkbox" id="<?php echo $pluginFile; ?>" name="<?php echo $pluginFile; ?>" value="true"<?php echo( intval( $pluginSettings['disabled'] ) === 1 ? ' checked' : ''); ?><?php echo( $pluginInfo['isActive'] === 'false' ? ' disabled' : ''); ?>>
										<label for="<?php echo $pluginFile; ?>">&nbsp;<?php echo $pluginInfo['Name']; ?></label>
										</div>
										<?php
									}
									?>
									<?php submit_button(); ?>
								</div>
								
								<div class="pkm-panel pkm-panel-primary-alt fadey">
									<h2>Additional Settings</h2>
									<?php
									$scheduleOptions = array(
										[ 'slug' => 'every_five_minutes', 'name' => 'Every 5 Minutes' ],
										[ 'slug' => 'hourly', 'name' => 'Hourly' ],
										[ 'slug' => 'daily', 'name' => 'Daily' ],
										[ 'slug' => 'weekly', 'name' => 'Weekly' ],
										[ 'slug' => 'disabled', 'name' => 'Never' ],
									);
									$scheduleOptions = array_reverse( $scheduleOptions );
									?>
									<div class="field-wrap">
										<big>Flush Posts Cache Schedule</big>
										<span class="dashicons dashicons-clock<?php echo( $this->settings['schedule']['value'] === 'disabled' ? ' off' : '' ); ?>"></span>
										<?php
										foreach( $scheduleOptions as $scheduleOption )
										{
										?>
										<span>
											<input type="radio" name="schedule" id="schedule-<?php echo $scheduleOption['slug']; ?>" value="<?php echo $scheduleOption['slug']; ?>"<?php echo( $scheduleOption['slug'] === $this->settings['schedule']['value'] ? ' checked' : ! isset( $this->settings['schedule'] ) && $scheduleOption['slug'] === 'daily' ? ' checked' : '' ); ?>>
											<label for="schedule-<?php echo $scheduleOption['slug']; ?>"<?php echo( $scheduleOption['slug'] === 'disabled' ? ' class="off"' : ''); ?>><?php echo $scheduleOption['name']; ?></label>
										</span>
										<?php
										}											
										?>
									</div>
									<div class="field-wrap">
										<big>Footer Credit Link</big>
										<span class="dashicons dashicons-admin-links<?php echo( $this->settings['credit']['value'] === 'disabled' ? ' off' : ! isset( $this->settings['credit'] ) ? ' off' : '' ); ?>"></span>
										<?php
										$debugOptions = array(
											[ 'slug' => 'enabled', 'name' => 'On' ],
											[ 'slug' => 'disabled', 'name' => 'Off' ],
										);
										$debugOptions = array_reverse( $debugOptions );
										foreach( $debugOptions as $debugOption )
										{
										?>
										<span>
											<input type="radio" name="credit" id="credit-<?php echo $debugOption['slug']; ?>" value="<?php echo $debugOption['slug']; ?>"<?php echo( $debugOption['slug'] === $this->settings['credit']['value'] ? ' checked' : ! isset( $this->settings['credit'] ) && $debugOption['slug'] === 'disabled' ? ' checked' : '' ); ?>>
											<label for="credit-<?php echo $debugOption['slug']; ?>"<?php echo( $debugOption['slug'] === 'disabled' ? ' class="off"' : ''); ?>><?php echo $debugOption['name']; ?></label>
										</span>
										<?php
										}											
										?>
									</div>
									<div class="field-wrap">
										<big>Debug Log</big>
										<span class="dashicons dashicons-admin-tools<?php echo( $this->settings['debug']['value'] === 'disabled' ? ' off' : ! isset( $this->settings['debug'] ) ? ' off' : '' ); ?>"></span>
										<?php
										$debugOptions = array(
											[ 'slug' => 'enabled', 'name' => 'On' ],
											[ 'slug' => 'disabled', 'name' => 'Off' ],
										);
										$debugOptions = array_reverse( $debugOptions );
										foreach( $debugOptions as $debugOption )
										{
										?>
										<span>
											<input type="radio" name="debug" id="debug-<?php echo $debugOption['slug']; ?>" value="<?php echo $debugOption['slug']; ?>"<?php echo( $debugOption['slug'] === $this->settings['debug']['value'] ? ' checked' : ! isset( $this->settings['debug'] ) && $debugOption['slug'] === 'disabled' ? ' checked' : '' ); ?>>
											<label for="debug-<?php echo $debugOption['slug']; ?>"<?php echo( $debugOption['slug'] === 'disabled' ? ' class="off"' : ''); ?>><?php echo $debugOption['name']; ?></label>
										</span>
										<?php
										}											
										?>
									</div>
									<div class="clear"></div>
									<?php submit_button(); ?>
								</div>
								<?php wp_nonce_field( $this->nonceAction, $this->nonceName ); ?>
							</form>
						</div>
					</li>
					<li>
						<input id="tab-3" type="radio" name="tabs">
						<label for="tab-3">&quot;It Broke My Site!&quot;</label>
						<div>
							<div class="pkm-panel pkm-panel-warning fadey">
								<h2>Troubleshooting Errors</h2>
								<p>I've tested <?php echo $this->pluginName; ?> on several sites I've built and it works fine.</p>
								<p>However, I cannot take in account conflicts with the thousands of Plugins and Themes from other sources, some of which <em>may</em> be poorly coded.</p>
								<p><strong>If this happens to you, please do the following steps, having your home page open in another browser. Or log out after each setting change if using the same browser. After each step refresh your home page TWICE</strong></p>
								<ol class="big">
									<li>Switch your site's theme to &quot;Twenty Nineteen&quot; &#40;or one of the other &quot;Twenty&hellip;&quot; Themes&#41;. If it then works, you have a moody theme</li>
									<li>If still broken, go to the <a href="<?php echo admin_url( 'plugins.php' ); ?>">WordPress<sup>&reg;</sup> Plugins page</a> and disable all Plugins &#40;except <?php echo $this->pluginName; ?>, obviously&#41;. If <?php echo $this->pluginName; ?> starts to work, we have a plugin conflit</li>
									<li>Reactivate each plugin one by one and refresh your home page each time time until it breaks</li>
									<li>If <em>still</em> broken after the above step, go to the <label for="tab-2">Settings tab</label> and try disabling <abbr title="JavaScript">JS</abbr> optimisation for the Plugin which triggered an error in the previous step - this is done in the second section &quot;Plugins JavaScript&quot;</li>
									<li>Finally, if no improvement has occurred, go to the <label for="tab-2">Settings tab</label> and experiment with toggling options in the first section &quot;Core Settings&quot;</li>
									<li>If you have the time, it would help greatly if you can <a href="https://wordpress.org/support/plugin/wp-roids" target="_blank" title="Opens in new tab/window">log an issue on the Support Page</a> and tell me as much as you can about what happened</li>
								</ol>

								<p>I will respond to issues as quickly as possible</p>
							</div>
						</div>
					</li>
					<?php
					if( $this->debug === TRUE )
					{ 
					?>
					<li>
						<input id="tab-4" type="radio" name="tabs">
						<label for="tab-4">Debug Log</label>
						<div>
							<?php $this->wpRoidsDebug(); ?>
						</div>
					</li>
					<?php
					}
					?>
				</ul>
			</div>
			<?php
			
		} // END adminPage()
		
	} // END class WPRoidsPhil
	
	// fire her up!
	WPRoidsPhil::instance();
	
} // END if class_exists()
