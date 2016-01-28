<?php

/**
 * The file that defines the core plugin class
 *
 * @link       http://webmaestro.fr
 * @since      2.0.0
 *
 * @package    WM_Settings
 * @subpackage WM_Settings/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization and hooks.
 *
 * Also maintains the unique identifier of this plugin, the full path and URL,
 * as well as the current version of the plugin.
 *
 * @since      2.0.0
 * @package    WM_Settings
 * @subpackage WM_Settings/includes
 * @author     Étienne Baudry <etienne@webmaestro.fr>
 */
class WM_Settings
{
    public static $path,          // Plugin directory path
        $url,                     // Plugin directory URL
        $name    = 'wm-settings', // Plugin name
        $version = '2.0.0';       // Plugin version

    private static $pages = array();


    // SETUP

    public static function setup( $path, $url )
    {
        self::$path = $path;
        self::$url = $url;

        // Includes
        require_once( "{$path}includes/class-wm-settings-page.php" );
        require_once( "{$path}includes/class-wm-settings-section.php" );
        require_once( "{$path}includes/class-wm-settings-field.php" );

        // Hooks
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 101 );
        add_action( 'customize_register', array( __CLASS__, 'customize_register' ) );
        add_action( 'activated_plugin', array( __CLASS__, 'activated_plugin' ) );
        add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ) );
    }


    // USER METHODS

    /**
     * Register a configuration page and its menu.
     *
     * @since 2.0.0
     *
     * @see wm_settings_add_page()
     */
    public static function add_page( $page_id, $title = null, $menu = true, array $config = null, $sections = null )
    {
        $page_key = sanitize_key( $page_id );
        return self::$pages[$page_key] = new WM_Settings_Page( $page_id, $title, $menu, $config, $sections );
    }

    /**
     * Get settings page.
     *
     * @since 2.0.0
     *
     * @param string $page_id Page identifier.
     * @return WM_Settings_Page Returns the page instance, or null if not found.
     */
    public static function get_page( $page_id )
    {
        $page_key = sanitize_key( $page_id );
        return empty( self::$pages[$page_key] ) ? null : self::$pages[$page_key];
    }


    // TRIGGERS

    // Register menu items
    public static function admin_menu()
    {
        // Public hook to register pages
        do_action( 'wm_settings_register' );
    }

    public static function customize_register( $wp_customize )
    {
        // WP_Customize_Control is not extendable before that
        require_once( self::$path . "includes/class-wm-settings-customize.php" );
        $panel = new WM_Settings_Customize();
        // Public hook to register customizer sections and fields
        do_action( 'wm_settings_customize', $panel );
        $panel->register( $wp_customize );
    }


    // WORDPRESS ACTIONS

    public static function activated_plugin()
    {
        $active_plugins = get_option( 'active_plugins' );
        // This plugin may be used by others, so let's load it first
        if ( $position = array_search( self::$name, $active_plugins ) ) {
            array_splice( $active_plugins, $position, 1 );
            array_unshift( $active_plugins, self::$name );
            update_option( 'active_plugins', $active_plugins );
        }
    }

    public static function plugins_loaded()
    {
        // Define internationalization
        load_plugin_textdomain( self::$name, false, self::$path . "languages/" );
        // Record plugin version (for coming updates)
        if ( ! get_option( 'wm_settings_version' ) ) {
            update_option( 'wm_settings_version', self::$version );
        }
    }

    // Page scripts and styles
    public static function admin_enqueue_scripts()
    {
        // Media upload
        wp_enqueue_media();
        // Main script
        wp_enqueue_script( self::$name, self::$url . "js/wm-settings.js", array( 'jquery', 'wp-color-picker' ), null, true );
        // Data
        wp_localize_script( self::$name, 'wmAjaxUrl', admin_url( "admin-ajax.php" ) );
        // Styles
        wp_enqueue_style( self::$name, self::$url . "css/wm-settings.css" );
        wp_enqueue_style( 'wp-color-picker' );
    }
}

?>
