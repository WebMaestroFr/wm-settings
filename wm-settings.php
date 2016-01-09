<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes the core plugin class and public user functions.
 *
 * @link              http://webmaestro.fr
 * @since             3.0.0
 * @package           WM_Settings
 *
 * @wordpress-plugin
 * Plugin Name:       WebMaestro Settings
 * Plugin URI:        http://webmaestro.fr/wordpress-wm-settings-plugin
 * Description:       Clean and simple options system for developers. Easy admin pages and notices with the WordPress Settings API.
 * Version:           3.0.0
 * Author:            Étienne Baudry
 * Author URI:        http://webmaestro.fr
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wm-settings
 * Domain Path:       /languages
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	exit();
}

// Avoid multiple declarations of the plugin
if ( ! class_exists( 'WM_Settings' ) ) {
	$wm_settings_path = plugin_dir_path( __FILE__ );
	// Include the core plugin class
	require_once( "{$wm_settings_path}includes/class-wm-settings.php" );
	// Include the public user functions
	require_once( "{$wm_settings_path}functions.php" );
	// Initialise the plugin
	WM_Settings::setup( $wm_settings_path, plugin_dir_url( __FILE__ ) );

	require_once( "{$wm_settings_path}examples.php" );
}
