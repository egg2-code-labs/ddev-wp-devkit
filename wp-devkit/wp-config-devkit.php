<?php
/**
 * #ddev-generated
 * wp-devkit runtime, loaded from wp-config.php before wp-config-ddev.php.
 *
 * Everything here only runs inside DDEV and never writes to the database:
 * plugins are disabled by filtering the active plugin lists at runtime, so the
 * database keeps the same state as the environment it was imported from.
 */

if ( getenv( 'IS_DDEV_PROJECT' ) !== 'true' ) {
	return;
}

/**
 * Read a WPDEV_* environment variable.
 */
function wpdev_env( $name, $default = '' ) {
	$value = getenv( $name );
	return ( false === $value || '' === $value ) ? $default : $value;
}

function wpdev_env_bool( $name, $default ) {
	$value = getenv( $name );
	if ( false === $value || '' === $value ) {
		return $default;
	}
	return in_array( strtolower( $value ), array( '1', 'true', 'yes', 'on' ), true );
}

function wpdev_env_list( $name, $default = '' ) {
	return array_values( array_filter( preg_split( '/[\s,]+/', wpdev_env( $name, $default ) ) ) );
}

// Alternative database (used by "wpdev golive" to work on a copy).
if ( wpdev_env( 'WPDEV_DB_NAME' ) ) {
	defined( 'DB_NAME' ) || define( 'DB_NAME', wpdev_env( 'WPDEV_DB_NAME' ) );
}

// Table prefix.
if ( ! isset( $table_prefix ) || '' === $table_prefix ) {
	$table_prefix = wpdev_env( 'WPDEV_TABLE_PREFIX', 'wp_' );
}

// Debugging: log everything, display nothing (old plugins are noisy).
defined( 'WP_DEBUG' ) || define( 'WP_DEBUG', true );
defined( 'WP_DEBUG_LOG' ) || define( 'WP_DEBUG_LOG', true );
defined( 'WP_DEBUG_DISPLAY' ) || define( 'WP_DEBUG_DISPLAY', false );
defined( 'WP_ENVIRONMENT_TYPE' ) || define( 'WP_ENVIRONMENT_TYPE', 'local' );

// Page caches (advanced-cache.php drop-ins) off.
defined( 'WP_CACHE' ) || define( 'WP_CACHE', false );

// No cron: imported databases contain due events (mails, API calls, imports).
if ( wpdev_env_bool( 'WPDEV_DISABLE_CRON', true ) ) {
	defined( 'DISABLE_WP_CRON' ) || define( 'DISABLE_WP_CRON', true );
}

// Optional: block outgoing HTTP requests except for allowed hosts.
if ( wpdev_env_bool( 'WPDEV_BLOCK_EXTERNAL_HTTP', false ) ) {
	defined( 'WP_HTTP_BLOCK_EXTERNAL' ) || define( 'WP_HTTP_BLOCK_EXTERNAL', true );
	defined( 'WP_ACCESSIBLE_HOSTS' ) || define( 'WP_ACCESSIBLE_HOSTS', wpdev_env( 'WPDEV_HTTP_ALLOW', '*.wordpress.org' ) );
}

// Multisite.
$wpdev_multisite = wpdev_env( 'WPDEV_MULTISITE' );
if ( in_array( $wpdev_multisite, array( 'subdirectory', 'subdomain' ), true ) ) {
	$wpdev_host = parse_url( wpdev_env( 'DDEV_PRIMARY_URL' ), PHP_URL_HOST );
	defined( 'WP_ALLOW_MULTISITE' ) || define( 'WP_ALLOW_MULTISITE', true );
	defined( 'MULTISITE' ) || define( 'MULTISITE', true );
	defined( 'SUBDOMAIN_INSTALL' ) || define( 'SUBDOMAIN_INSTALL', 'subdomain' === $wpdev_multisite );
	defined( 'DOMAIN_CURRENT_SITE' ) || define( 'DOMAIN_CURRENT_SITE', $wpdev_host );
	defined( 'PATH_CURRENT_SITE' ) || define( 'PATH_CURRENT_SITE', '/' );
	defined( 'SITE_ID_CURRENT_SITE' ) || define( 'SITE_ID_CURRENT_SITE', 1 );
	defined( 'BLOG_ID_CURRENT_SITE' ) || define( 'BLOG_ID_CURRENT_SITE', 1 );
	unset( $wpdev_host );
}
unset( $wpdev_multisite );

/**
 * Plugins that must not run locally: caches, security/2FA, backups, analytics,
 * external mailers. Matched by plugin folder (or single-file plugin name).
 */
const WPDEV_DEFAULT_DISABLED_PLUGINS = '
	wp-super-cache w3-total-cache wp-rocket litespeed-cache wp-fastest-cache autoptimize breeze sg-cachepress
	wordfence better-wp-security ithemes-security-pro sucuri-scanner all-in-one-wp-security-and-firewall wp-2fa two-factor
	updraftplus backwpup duplicator duplicator-pro all-in-one-wp-migration backup-backup
	google-site-kit wp-statistics google-analytics-for-wordpress jetpack
	wp-mail-smtp post-smtp fluent-smtp easy-wp-smtp
';

$GLOBALS['wpdev_disabled_plugins'] = array_values(
	array_diff(
		array_merge(
			wpdev_env_list( 'WPDEV_DISABLE_PLUGINS', WPDEV_DEFAULT_DISABLED_PLUGINS ),
			wpdev_env_list( 'WPDEV_DISABLE_PLUGINS_EXTRA' )
		),
		wpdev_env_list( 'WPDEV_KEEP_PLUGINS' )
	)
);

function wpdev_is_disabled_plugin( $plugin_file ) {
	$slug = strtok( $plugin_file, '/' );
	$slug = preg_replace( '/\.php$/', '', $slug );
	return in_array( $slug, $GLOBALS['wpdev_disabled_plugins'], true );
}

// Raw (unfiltered) lists, so saving the plugin screen cannot persist the removal.
$GLOBALS['wpdev_raw_plugins'] = array();

function wpdev_filter_active_plugins( $plugins ) {
	if ( ! is_array( $plugins ) ) {
		return $plugins;
	}
	$GLOBALS['wpdev_raw_plugins'][ get_current_blog_id() ] = $plugins;
	return array_values( array_filter( $plugins, function ( $plugin ) {
		return ! wpdev_is_disabled_plugin( $plugin );
	} ) );
}

function wpdev_filter_sitewide_plugins( $plugins ) {
	if ( ! is_array( $plugins ) ) {
		return $plugins;
	}
	$GLOBALS['wpdev_raw_plugins']['network'] = $plugins;
	return array_filter( $plugins, function ( $plugin ) {
		return ! wpdev_is_disabled_plugin( $plugin );
	}, ARRAY_FILTER_USE_KEY );
}

function wpdev_preserve_active_plugins( $new ) {
	$raw = isset( $GLOBALS['wpdev_raw_plugins'][ get_current_blog_id() ] ) ? $GLOBALS['wpdev_raw_plugins'][ get_current_blog_id() ] : array();
	$keep = array_filter( $raw, 'wpdev_is_disabled_plugin' );
	return is_array( $new ) ? array_values( array_unique( array_merge( $new, $keep ) ) ) : $new;
}

function wpdev_preserve_sitewide_plugins( $new ) {
	$raw = isset( $GLOBALS['wpdev_raw_plugins']['network'] ) ? $GLOBALS['wpdev_raw_plugins']['network'] : array();
	$keep = array_filter( $raw, 'wpdev_is_disabled_plugin', ARRAY_FILTER_USE_KEY );
	return is_array( $new ) ? $new + $keep : $new;
}

// Route every mail through Mailpit, even if an SMTP plugin configured a remote host.
function wpdev_force_mailpit( $phpmailer ) {
	$phpmailer->isSMTP();
	$phpmailer->Host       = '127.0.0.1';
	$phpmailer->Port       = 1025;
	$phpmailer->SMTPAuth   = false;
	$phpmailer->SMTPSecure = '';
	$phpmailer->SMTPAutoTLS = false;
}

function wpdev_admin_notice() {
	$disabled = array();
	foreach ( $GLOBALS['wpdev_raw_plugins'] as $list ) {
		foreach ( (array) $list as $key => $value ) {
			$file = is_string( $key ) ? $key : $value;
			if ( wpdev_is_disabled_plugin( $file ) ) {
				$disabled[] = strtok( $file, '/' );
			}
		}
	}
	$disabled = array_unique( $disabled );
	if ( $disabled && current_user_can( 'activate_plugins' ) ) {
		printf(
			'<div class="notice notice-info"><p><strong>wp-devkit:</strong> %s</p></div>',
			esc_html( 'Disabled locally (still active in the database): ' . implode( ', ', $disabled ) )
		);
	}
}

/**
 * Register hooks before WordPress loads, via the pre-initialised $wp_filter
 * global (supported since WP 4.7). Needed because network-activated plugins
 * load before any mu-plugin hook fires.
 */
function wpdev_add_early_hook( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['wp_filter'][ $hook ][ $priority ][] = array(
		'function'      => $callback,
		'accepted_args' => $args,
	);
}

if ( $GLOBALS['wpdev_disabled_plugins'] ) {
	wpdev_add_early_hook( 'option_active_plugins', 'wpdev_filter_active_plugins', PHP_INT_MAX );
	wpdev_add_early_hook( 'site_option_active_sitewide_plugins', 'wpdev_filter_sitewide_plugins', PHP_INT_MAX );
	wpdev_add_early_hook( 'pre_update_option_active_plugins', 'wpdev_preserve_active_plugins', PHP_INT_MAX );
	wpdev_add_early_hook( 'pre_update_site_option_active_sitewide_plugins', 'wpdev_preserve_sitewide_plugins', PHP_INT_MAX );
	wpdev_add_early_hook( 'admin_notices', 'wpdev_admin_notice', 10, 0 );
	wpdev_add_early_hook( 'network_admin_notices', 'wpdev_admin_notice', 10, 0 );
}

wpdev_add_early_hook( 'phpmailer_init', 'wpdev_force_mailpit', PHP_INT_MAX );

// Action Scheduler (WooCommerce, The Events Calendar, ...) also runs queues on admin requests.
if ( wpdev_env_bool( 'WPDEV_DISABLE_CRON', true ) ) {
	wpdev_add_early_hook( 'action_scheduler_allow_async_request_runner', '__return_false', PHP_INT_MAX );
}

/**
 * Edit detection for "ddev dbsync" (WPDEV_DB_CHANGE_DETECTION=edits, the default).
 *
 * Plugins write to the database all the time (caches, counters, notices, update
 * checks), so comparing database contents cannot tell real edits from noise
 * without a rule per plugin. Instead, a database write counts as an edit when it
 * happens in an edit context:
 *
 * - a logged-in user sends a non-GET request (saving a post, settings, media,
 *   page builders, REST/AJAX saves), or
 * - a logged-in user opens an admin URL with a nonce (activating a plugin,
 *   trashing a post and other admin actions that use GET links), or
 * - a WP-CLI command (except cron/transient/cache housekeeping).
 *
 * Each edit appends one line to .ddev/.wpdev/dirty; the pre-commit hook exports
 * the database when that file exists. Writes by anonymous visitors, by page
 * views (GET) and by the WordPress heartbeat never count.
 */
const WPDEV_DIRTY_FILE = '/var/www/html/.ddev/.wpdev/dirty';

// Request signatures ("METHOD /path action=..." or "cli <command>") that never count
// as edits: login, heartbeat, screen options, statistics hits, WP-CLI housekeeping.
// Projects add their own with WPDEV_DIRTY_IGNORE_EXTRA (one regex).
const WPDEV_DIRTY_IGNORE = array(
	'^[A-Z]+ /wp-login\.php',
	'^POST /wp-admin/admin-ajax\.php action=(heartbeat|wp-remove-post-lock|closed-postboxes|meta-box-order|hidden-columns|dismiss-wp-pointer)$',
	'/wp-statistics/',
	'^POST /wp-json/\S*/hit$',
	'^cli (cron|transient|cache)( |$)',
	'^cli rewrite flush$',
);

$GLOBALS['wpdev_db_written'] = false;

function wpdev_detect_writes( $query ) {
	if ( ! $GLOBALS['wpdev_db_written']
		&& preg_match( '/^\s*(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE|RENAME)\b/i', $query )
		&& ! wpdev_is_transient_write( $query ) ) {
		$GLOBALS['wpdev_db_written'] = true;
	}
	return $query;
}

/**
 * Writes that only set or expire transients (caches). Matches the option/meta
 * key, not the value: saving a setting whose value contains "_transient_" counts.
 */
function wpdev_is_transient_write( $query ) {
	// '_transient_', '_site_transient_', also LIKE-escaped by prepare(): '\\_transient\\_%'.
	$key = '\'\\\\*_(site\\\\*_)?transient\\\\*_';
	// add_option()/set_site_transient(): the key is the first value (sitemeta: after site_id).
	$insert = '^\s*(INSERT|REPLACE)\s+(IGNORE\s+)?INTO\s+`?\w*(options|sitemeta)`?\s*\([^)]*\)\s*VALUES\s*\(\s*(\d+\s*,\s*)?' . $key;
	// update_option(), delete_option(), delete_expired_transients(): the key is in WHERE.
	$where = '^\s*(UPDATE|DELETE)\b.*\bWHERE\s+(\w+\.)?`?(option_name|meta_key)`?\s*(=|LIKE)\s*' . $key;
	return (bool) preg_match( '/' . $insert . '|' . $where . '/is', $query );
}

/**
 * Describe the current request, e.g. "POST /wp-admin/post.php action=editpost"
 * or "cli post update". Returns null outside an edit context.
 */
function wpdev_edit_signature() {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		$args = class_exists( 'WP_CLI' ) ? WP_CLI::get_runner()->arguments : array();
		return 'cli ' . implode( ' ', array_slice( (array) $args, 0, 2 ) );
	}
	if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
		return null;
	}
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';
	$is_get = in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true );
	if ( $is_get && ! ( is_admin() && isset( $_REQUEST['_wpnonce'] ) ) ) {
		return null;
	}
	$path = (string) parse_url( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH );
	// Relative to the WordPress installation (core may live in a subdirectory), so
	// "/wp-admin/..." patterns match everywhere.
	foreach ( array( site_url(), home_url() ) as $base ) {
		$base = rtrim( (string) parse_url( $base, PHP_URL_PATH ), '/' );
		if ( '' !== $base && 0 === strpos( $path, $base . '/' ) ) {
			$path = substr( $path, strlen( $base ) );
			break;
		}
	}
	$signature = $method . ' ' . $path;
	if ( isset( $_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) ) {
		$signature = $method . ' /wp-json' . $_GET['rest_route'];
	}
	if ( isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ) {
		$signature .= ' action=' . $_REQUEST['action'];
	}
	return $signature;
}

function wpdev_record_edit() {
	if ( ! $GLOBALS['wpdev_db_written'] ) {
		return;
	}
	$signature = wpdev_edit_signature();
	if ( null === $signature ) {
		return;
	}
	foreach ( array_filter( array_merge( WPDEV_DIRTY_IGNORE, array( wpdev_env( 'WPDEV_DIRTY_IGNORE_EXTRA' ) ) ) ) as $pattern ) {
		if ( @preg_match( '#' . str_replace( '#', '\#', $pattern ) . '#', $signature ) ) {
			return;
		}
	}
	$dir = dirname( WPDEV_DIRTY_FILE );
	if ( ! is_dir( $dir ) ) {
		@mkdir( $dir, 0775, true );
		@file_put_contents( $dir . '/.gitignore', "*\n" );
	}
	$user = function_exists( 'wp_get_current_user' ) ? wp_get_current_user()->user_login : '';
	@file_put_contents(
		WPDEV_DIRTY_FILE,
		gmdate( 'Y-m-d\TH:i:s\Z' ) . "\t" . $signature . ( $user ? "\t" . $user : '' ) . "\n",
		FILE_APPEND | LOCK_EX
	);
}

if ( 'edits' === wpdev_env( 'WPDEV_DB_CHANGE_DETECTION', 'edits' ) && ! wpdev_env_bool( 'WPDEV_NO_DIRTY', false ) ) {
	wpdev_add_early_hook( 'query', 'wpdev_detect_writes', PHP_INT_MAX );
	wpdev_add_early_hook( 'shutdown', 'wpdev_record_edit', PHP_INT_MAX, 0 );
}
