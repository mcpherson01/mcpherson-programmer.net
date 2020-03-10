<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'q$ZzYZAG_4;qs~Z>BeY:?Etj7Jk8iRb|&S25Iq4loLlx0;yQ)imgc&*%s,P|cU~V' );
define( 'SECURE_AUTH_KEY',  '7(.?R6<l,$x{Cwc-jRuM^+9no<x@0L?@AfKKuy f{:2`1)#Q,N3[*A1vIPT`H?PP' );
define( 'LOGGED_IN_KEY',    'M[51[%(HSdHUhAS#XkYYw7N_,eI[AjGe,b=yKSVv.$n]Im:W/o2CR7~dJ<FNq9f&' );
define( 'NONCE_KEY',        'F*}BKESDa5;:Qq%CGek*CwS{0xWG@1ibHgy&F>ry9qI%`CG4~gy=i{P_r%:~hzO,' );
define( 'AUTH_SALT',        'tyIVpoquP[-jb@KK3)7VJGB/PYC~cx#c)Nmwc1>f%uI)c)RxapXc[kNcnyF))][2' );
define( 'SECURE_AUTH_SALT', 'bB[vb[,!FF0GY8ZCo89PmJTp>}&Z(z,nuA(MkdT8V]P_q`6V2*gS+(lxhq=GnT?%' );
define( 'LOGGED_IN_SALT',   '_8+s:8:aa`Ek{B!?[P%z0XgZ7AgT#!XY_X08{ =kC60j_gJbJDH{$*nHZ+o.4F-R' );
define( 'NONCE_SALT',       '>Iqn&S`yi,%-#KHz3xQWV[_`yYsHv}sZR7D^KT34|vjoT1<%^X5X*-Wi1,/g]1.[' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
