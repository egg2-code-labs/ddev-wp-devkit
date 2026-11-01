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
