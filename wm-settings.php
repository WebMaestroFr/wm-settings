<?php

/**
 * @package     WM_Settings
 * @link        http://webmaestro.fr/wordpress-wm-settings-plugin
 * @author      Étienne Baudry <etienne@webmaestro.fr>
 *
 * @wordpress-plugin
 * Plugin Name: WebMaestro Settings
 * Plugin URI:  http://webmaestro.fr/wordpress-wm-settings-plugin
 * Description: Generic settings pages, form sections and fields for the dashboard or the customizer. Based on WordPress APIs, for themes and plugins developers.
 * Version:     2.0.0
 * Author:      Étienne Baudry
 * Author URI:  http://webmaestro.fr
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wm-settings
 * Domain Path: /languages
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
