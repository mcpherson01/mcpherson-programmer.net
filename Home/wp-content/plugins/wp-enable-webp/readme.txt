=== WP Enable WebP ===
Contributors: louis
Tags: WebP, Image Format
Requires at least: 4.3
Tested up to: 5.2.2
Stable tag: 3.6

License: GPLv2 or later

WordPress plugin to enable WebP image uploads.

== Description ==

This WordPress Plugin for WebP image uploads enabled WebP image uploads into the media library. 

It will also add a class to the body tag for CSS selectors. This is helpful for setting background images. 

Example: 
#selector {
  background: url('images/example.jpg');
}

body.supports-webp #selector {
  background: url('images/example.webp');
}

Use the function webpSupported() in template files to conditionally show WebP images. 

Example:
if (webpSupported()) :
    WebP image here
 else:
    Image with other fallback format here
 endif;


Major features in WP Enable WebP include:

* Enabling WebP Image uploads into the media library. 
* Browser detection that adds a class to the body tag indicating WebP is supported by the browser.
* Helper function to use in theme template files to check if the browser supports WebP image format.  

== Installation ==

Upload the "wp-enable-webp" folder to your wp-content/plugins directory, then Activate it. 

== Changelog ==

= 1.0 =
*Release Date - 19 December 2018*
* Initial release
