<?php
/*
Plugin Name: WebMaestro Settings
Plugin URI: http://webmaestro.fr/wordpress-settings-api-options-pages/
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Clean and simple options system for developers. Easy admin pages and notices with the WordPress Settings API.
Version: 2.0.1
License: GNU General Public License
License URI: license.txt
Text Domain: wm-settings
*/

require_once( __DIR__ . '/class.wm-notices.php' );
require_once( __DIR__ . '/class.wm-settings.php' );

// This plugin may be used by other ones, so let's try to load it first
function wm_settings_plugin_priority()
{
    $wm_settings = plugin_basename( __FILE__ );
    $active_plugins = get_option( 'active_plugins' );
    if ( $position = array_search( $wm_settings, $active_plugins ) ) {
        array_splice( $active_plugins, $position, 1 );
        array_unshift( $active_plugins, $wm_settings );
        update_option( 'active_plugins', $active_plugins );
    }
}
add_action( 'activated_plugin', 'wm_settings_plugin_priority' );
