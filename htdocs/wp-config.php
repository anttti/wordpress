<?php
##############################################################################
##### We highly suggest not put anything sensitive in this file directly #####
##### Use .env instead. Instructions in http://wp-palvelu.fi/ohjeet      #####
##############################################################################

#Load composer libraries
require_once(dirname(__DIR__) . '/vendor/autoload.php');

$root_dir = dirname(__DIR__);
$webroot_dir = $root_dir . '/htdocs';

/**
 * Use Dotenv to set required environment variables and load .env file in root
 * WP-Palvelu provides all needed envs for wordpress by default.
 * If you want to have more envs put them into .env file
 * .env file is also heavily used in development
 */
if (file_exists($root_dir . '/.env')) {
  Dotenv::load($root_dir);
}


/**
 * DB settings
 * You can find the credentials by running $ wp-list-env
 */
define('DB_NAME', getenv('DB_NAME'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : 'localhost' );
define('DB_PORT', getenv('DB_PORT') ? getenv('DB_PORT') : 3306 );
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
$table_prefix = getenv('DB_PREFIX') ? getenv('DB_PREFIX') : 'wp_';

/**
 * Redis as object cache
 * You need to have this plugin in https://wordpress.org/plugins/wp-redis/
 * wp-content/object-cache.php in order to use redis for transients and cache
 */
$redis_server = array( 'host' => getenv('REDIS_HOST'), 'port' => getenv('REDIS_PORT'));

/**
 * WordPress Localized Language
 */
define('WPLANG', 'fi');

/**
 * Content Directory is moved out of the wp-core.
 */
define('CONTENT_DIR', '/wp-content');
define('WP_CONTENT_DIR', $webroot_dir . CONTENT_DIR);
define('WP_CONTENT_URL', CONTENT_DIR);

/**
 * Don't allow any other write method than direct
 */
define( 'FS_METHOD', 'direct' );

/**
 * Authentication Unique Keys and Salts
 * You can find them by running $ wp-list-env
 */
define('AUTH_KEY',         getenv('AUTH_KEY'));
define('SECURE_AUTH_KEY',  getenv('SECURE_AUTH_KEY'));
define('LOGGED_IN_KEY',    getenv('LOGGED_IN_KEY'));
define('NONCE_KEY',        getenv('NONCE_KEY'));
define('AUTH_SALT',        getenv('AUTH_SALT'));
define('SECURE_AUTH_SALT', getenv('SECURE_AUTH_SALT'));
define('LOGGED_IN_SALT',   getenv('LOGGED_IN_SALT'));
define('NONCE_SALT',       getenv('NONCE_SALT'));

/**
 * SSL Admin
 */
define('FORCE_SSL_ADMIN', true);

/**
 * Use *.seravo.fi domain as the wp-admin
 */
if (getenv('HTTPS_DOMAIN_ALIAS'))
  define('HTTPS_DOMAIN_ALIAS', getenv('HTTPS_DOMAIN_ALIAS'));

/**
 * Custom Settings
 */
define('AUTOMATIC_UPDATER_DISABLED', true); /* automatic updates are handled by wordpress-palvelu */
define('DISALLOW_FILE_EDIT', true); /* this disables the theme/plugin file editor */
define('PLL_COOKIE', false); /* this allows caching sites with polylang, disable if weird issues occur */

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);

/**
 * Log error data but don't show it in the frontend.
 */
ini_set('log_errors', 'On');

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
  define('ABSPATH', $webroot_dir . '/wordpress/');
}

require_once(ABSPATH . 'wp-settings.php');
