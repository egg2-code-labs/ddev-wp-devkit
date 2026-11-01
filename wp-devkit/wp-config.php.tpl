<?php
/**
 * #ddev-generated
 * #wpdev-generated: Created by the ddev-wp-devkit add-on for local development.
 * Remove this line to take ownership; wp-devkit will then never touch this file.
 * Keep this file out of git and never deploy it.
 */

// Optional project-specific settings, committed to the repo.
if ( file_exists( '/var/www/html/.ddev/wp-config-project.php' ) ) {
	require '/var/www/html/.ddev/wp-config-project.php';
}

// wp-devkit runtime: debug, cache, cron, mail, multisite, disabled plugins.
if ( file_exists( '/var/www/html/.ddev/wp-devkit/wp-config-devkit.php' ) ) {
	require_once '/var/www/html/.ddev/wp-devkit/wp-config-devkit.php';
}

// Database credentials and URLs, managed by DDEV.
require __DIR__ . '/wp-config-ddev.php';

__SALTS__

// Find wp-settings.php like DDEV does: in this directory or in one subdirectory.
$wpdev_abspath = __DIR__ . '/';
if ( ! file_exists( __DIR__ . '/wp-settings.php' ) ) {
	foreach ( glob( __DIR__ . '/*/wp-settings.php' ) ?: array() as $wpdev_wp_settings ) {
		$wpdev_abspath = dirname( $wpdev_wp_settings ) . '/';
		break;
	}
}
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $wpdev_abspath );
}
unset( $wpdev_abspath, $wpdev_wp_settings );

require_once ABSPATH . 'wp-settings.php';
