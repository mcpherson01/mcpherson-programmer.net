=== WP Roids ===

Contributors: philmeadows
Donate link: https://philmeadows.com/say-thank-you/
Tags: cache,caching,minify,page speed,optimize,performance,compression
Requires at least: 4.2
Tested up to: 5.2.3
Requires PHP: 5.4.0
Stable tag: 3.2.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0-standalone.html

Best caching, minification and compression for WordPress. Tested FASTER than: WP Super Cache, W3 Total Cache, WP Fastest Cache and many others!

== Description ==

**Fast AF caching! Optimize your site's HTML, CSS, JavaScript AND images**

- Minifies and strips comments out of HTML
- Generates static HTML pages
- Minifies CSS and generates single compiled CSS files per page
- Minifies JavaScript and generates single compiled JavaScript files per page
- Defers loading of generated JavaScript file
- Compiles WordPress Core jQuery and jQuery-migrate files into one file
- Compiles and minifies CSS and JavaScript loaded from external CDN sources
- Compresses JPEG and PNG images hosted in your site's Media Library
- Features above can be toggled on and off
- CSS and JavaScript optimisation can be toggled on and off on a per Plugin basis

= Getting Started =

No complicated settings to deal with;

1. Deactivate/delete any current caching or minification plugins
2. Install WP Roids
3. Activate WP Roids
4. Log out
5. Refresh your home page TWICE

= "It broke my site, arrrrrrgh!" =

**Do the following steps, having your home page open in another browser. Or log out after each setting change if using the same browser. After each step refresh your home page TWICE**

1. Switch your site's theme to "Twenty Nineteen" (or one of the other "Twenty..." Themes). If it then works, you have a moody theme
2. If still broken, go to the WordPress Plugins page and disable all Plugins (except WP Roids, obviously). If WP Roids starts to work, we have a plugin conflit
3. Reactivate each plugin one by one and refresh your home page each time time until it breaks
4. If _still_ broken after the above step, go to the Settings tab and try disabling JS optimisation for the Plugin which triggered an error in the previous step - this is done in the second section "Plugins JavaScript"
5. Finally, if no improvement has occurred, go to the Settings tab and experiment with toggling options in the first section "Core Settings"
6. If you have the time, it would help greatly if you can [log a support topic on the Support Page](https://wordpress.org/support/plugin/wp-roids) and tell me as much as you can about what happened

= How Fast Is It? =

In testing, WP Roids was FASTER than:

- WP Super Cache
- W3 Total Cache
- WP Fastest Cache
- Comet Cache
- Autoptimize
- WP Speed of Light
- ...and many more!

= Where Can I Check Site Speed? =

Either of these two sites is good:

- [Pingdom](https://tools.pingdom.com)
- [GTmetrix](https://gtmetrix.com)

= Software Requirements =

In addition to the [WordPress Requirements](https://wordpress.org/about/requirements/), WP Roids requires the following:

-	**`.htaccess` file to be writable**

	some security plugins disable this, so just turn this protection off for a minute during install/activation	
-	**PHP version greater than 5.4.0**

	It WILL throw errors on activation if not! No damage should occur though	
-	**PHP cURL extension enabled**

	Usually is by default on most decent hosts

== Installation ==

**NOTE:** WP Roids requires the `.htaccess` file to have permissions set to 644 at time of activation. Some security plugins (quite rightly) change the permissions to disable editing. Please temporarily disable this functionality for a moment whilst activating WP Roids.

The quickest and easiest way to install is via the `Plugins > Add New` feature within WordPress. But if you must, manual installation instructions follow...

1. Download and unzip the latest release zip file.
2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
4. Activate the plugin through the 'Plugins' menu in WordPress Administration.

== Frequently Asked Questions ==

= Is WP Roids compatible with iThemes Security? =

Yes. But if you have `.htaccess` protected (**Security > Settings > System Tweaks**), you will need to disable this when activating WP Roids. You can re-enable it after activation

= Is WP Roids compatible with Jetpack? =

Yes

= Is WP Roids compatible with Yoast SEO? =

Yes

= Is WP Roids compatible with WooCommerce? =

Yes

= Is WP Roids compatible with Storefront Theme for WooCommerce? =

Yes

= Is WP Roids compatible with Storefront Pro (Premium) plugin for WooCommerce? =

Yes

= Is WP Roids compatible with WPBakery Visual Composer? =

Yes

= Is WP Roids compatible with Page Builder by SiteOrigin? =

Yes

= Is WP Roids compatible with Genesis Framework and Themes? =

Yes. Though I have only tested with Metro Pro theme

= Is WP Roids compatible with the "Instagram Feed" plugin? =

Yes, but you must tick the "Are you using an Ajax powered theme?" option to "Yes"

= Is WP Roids compatible with the "Disqus Comment System" plugin? =

Yes

= Is WP Roids compatible with the "Visual Form Builder" plugin? =

Yes

= Is WP Roids compatible with the "Simple Share Buttons Adder" plugin? =

Yes

= Is WP Roids compatible with the "Revolution Slider" plugin? =

Yes, but you have to disable minification and caching of its JavaScript in the WP Roids Settings

= Is WP Roids compatible with the "WordPress Gallery Plugin – NextGEN Gallery" plugin? =

No. This plugin intrusively removes queued JavaScript. [See this support question](https://wordpress.org/support/topic/all-marketing-crappy-product-does-not-even-follow-good-coding-practices)

= Is WP Roids compatible with Cloudflare? =

Yes, but you may want to flush your Cloudflare cache after activating WP Roids. Also disable the minification options at Cloudflare

= Does WP Roids work on NGINX servers? =

Maybe. [See this support question](https://wordpress.org/support/topic/nginx-15)

= Does WP Roids work on IIS (Windows) servers? =

Probably not

== Under The Hood ==

WP Roids decides which Pages and Posts are suitable for caching and/or minification of HTML, CSS & Javascript.

For HTML caching, image compression, and minification of CSS & JavaScript, the rules generally are:

- User is NOT logged in ~ might change this in future, Phil
- Current view IS a Page or Post (or Custom Post Type)
- Current view is NOT an Archive i.e. list of Posts (or Custom Post Types) ~ might change this in future, Phil
- Current view has NOT received an HTTP POST request e.g. a form submission
- Current view is NOT WooCommerce basket, checkout or "My Account" page

If any of the above rules fail, WP Roids will simply just minify the HTML output. All else gets the CSS & Javascript combined and minified (and requeued), then the Page/Post HTML is minified and a static `.html` file copy saved in the cache folder.

The cache automatically clears itself on Page/Post changes, Theme switches and Plugin activations/deactivations

WP Roids can also detect if you have manually edited a Theme CSS or JavaScript file and will update iteself with the new versions

== License ==

Copyright: © 2019 [Philip K. Meadows](https://philmeadows.com) (coded in Great Britain)

Released under the terms of the [GNU General Public License](https://www.gnu.org/licenses/gpl-3.0-standalone.html)

= Credits / Additional Acknowledgments =

* Software designed for WordPress
	- GPL License <https://codex.wordpress.org/GPL>
	- WordPress <https://wordpress.org>
* Photograph of Lance Armstrong
	- Source: <http://newsactivist.com/en/articles/604-103-lf/lance-armstrong-cheater-0>
	- Used without permission, but "Fair Use" for the purposes of caricature, parody and satire

== Upgrade Notice ==

= v3.2.0 =

Logic condition was preventing some storing of HTML files

= v3.1.1 =

Minor bug fix affecting some users

= v3.1.0 =

Fixes to image compression

= v3.0.0 =

Massive new release/overhaul, upgrade ASAFP!

= v2.2.0 =

More regex fixes on asset minification

= v2.1.1 =

Regex fixes for `data:image` and `base64` strings

= v2.1.0 =

Formatting error fix for Visual Composer

= v2.0.1 =

Minor tweak

= v2.0.0 =

Urgent update, install immediately!

= v1.3.6 =

Compatibility check that caused deactivation fixed

= v1.3.5 =

JS comment crash fixed

= v1.3.4 =

rewritebase error, was killing some sites dead AF, sorry

= v1.3.3 =

Minor fixes

= v1.3.1 =

Switched off debugger

= v1.3.0 =

Another HUGE update! Recommend deactivating and reactivating after update to ensure correct operation

= v1.2.0 =

HUGE update! Recommend deactivating and reactivating after update to ensure correct operation

= v1.1.4 =

More asset enqueuing issue fixes

= v1.1.3 =

Asset enqueuing issue fixes

= v1.1.2 =

Version numbering cockup

= v1.1.1 =

Issue fixes

= v1.1.0 =

Several improvements and issue fixes

= v1.0.0 =

Requires WordPress 4.2+

== Changelog ==

= v3.2.0 =

- **Fixed:** Logic condition was preventing some storing of HTML files
