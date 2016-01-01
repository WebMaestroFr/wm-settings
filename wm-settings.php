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
}


// TESTS
function test_register_pages() {
	$parent = wm_settings_add_page( 'my_parent_page', 'My Parent Page'  );
	$child = wm_settings_add_page( 'my_child_page', 'My Child Page', 'my_parent_page' );

	$section_one = $parent->add_section( 'section_one', 'Section One', null, array(
		'field_1' => 'My field',
		'field_2' => array( 'My checkbox', 'checkbox' )
	) );

	$section_two = $child->add_section( 'section_two', 'Section Two', array(
		'description' => 'Lorem ipsum dolor sit amet.',
		'submit'      => true,
		'reset'       => 'My Reset Text'
	), array(
		'field_3' => array( 'My textarea', 'textarea', array(
			'description' => 'Lorem ipsum dolor sit amet.',
			'default'     => 'My default.',
			'sanitize'    => 'strtoupper',
			'attributes'  => array(
				'style' => 'color: red'
			)
		) ),
		'field_4' => array( 'My multi', 'multi', array(
			// 'default'     => array( 'two' => 1 ),
			'choices'     => array( 'One', 'Two', 'Three' )
		) ),
		'field_5' => array( 'My action', 'action', array(
			'action'      => 'wp_send_json_success'
		) )
	) );

	$section_three = $child->add_section( 'section_three', 'Section Three', array(
		'submit'      => 'Custom Submit',
		'customize'   => true
	), array(
		'field_6' => array( 'My color', 'color' ),
		'field_7' => array( 'My image', 'image' ),
		'field_8' => array( 'My media', 'media' )
	) );
}
add_action( 'wm_settings_register_pages', 'test_register_pages' );
