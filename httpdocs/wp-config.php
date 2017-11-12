<?php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
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
define('DB_NAME', 'dotnayco');

/** MySQL database username */
define('DB_USER', 'dotnayco');

/** MySQL database password */
define('DB_PASSWORD', 'Y4r2q5N2db');

/** MySQL hostname */
define('DB_HOST', 'db');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'XW4yNc(U`84kq#&ya+_)>5p/3BLa63g{');
define('SECURE_AUTH_KEY', 'G^dpP8-#hVUj9`6,4<N^t]4L]^wUM*8Q');
define('LOGGED_IN_KEY', 'CN1mkCLVx@yJgr7E{seMQf65[8B=& p=');
define('NONCE_KEY', '>K!k$e78L.2La9:PYRb$-bVe<TCic1SL');
define('AUTH_SALT', 'ef082Q.2Tp{(ji*q)x_1[mKB/Z2yFQO]');
define('SECURE_AUTH_SALT', 'y eY6gcWbHUKh1k52%-0`b7aB3bijsQV');
define('LOGGED_IN_SALT', '7Z6O*6(]|89V.94v[5ceY;w=p/2;2wft');
define('NONCE_SALT', 'qePw26jD~*7jevV{0=)2J&|_kn7i[l^u');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'syld_';

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
