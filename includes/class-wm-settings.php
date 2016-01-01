<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://webmaestro.fr
 * @since      3.0.0
 *
 * @package    WM_Settings
 * @subpackage WM_Settings/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      3.0.0
 * @package    WM_Settings
 * @subpackage WM_Settings/includes
 * @author     Étienne Baudry <etienne@webmaestro.fr>
 */
class WM_Settings
{
    public static $path,
        $url,
        $name    = 'wm-settings',
        $version = '3.0.0';

    private static $pages = array();

    public static function setup( $path, $url )
    {
        self::$path = $path;
        self::$url = $url;

        require_once( "{$path}includes/class-wm-settings-page.php" );
        require_once( "{$path}includes/class-wm-settings-section.php" );
        require_once( "{$path}includes/class-wm-settings-field.php" );

        add_action( 'admin_menu',          array( __CLASS__, 'register_pages' ), 101 );
        add_action( 'customize_register',  array( __CLASS__, 'register_pages' ), 101 );
        add_action( 'wp_ajax_wm_settings', array( __CLASS__, 'register_pages' ), 101 );

        add_action( 'activated_plugin',    array( __CLASS__, 'activated_plugin' ) );
        add_action( 'plugins_loaded',      array( __CLASS__, 'plugins_loaded' ) );
    }


    // USER METHODS

    public static function add_page( $page_id, $title = null, $menu = true, array $config = null, $sections = null )
    {
        if ( ( $page_id = sanitize_key( $page_id ) ) && empty( self::$pages[$page_id] ) ) {
            return self::$pages[$page_id] = new WM_Settings_Page( $page_id, $title, $menu, $config, $sections );
        }
        return null;
    }

    public static function get_page( $page_id )
    {
        if ( ( $page_id = sanitize_key( $page_id ) ) && ! empty( self::$pages[$page_id] ) ) {
            return self::$pages[$page_id];
        }
        return null;
    }


    // TRIGGERS

    // Register menu items
    public static function register_pages()
    {
        // Public hook to register pages
        do_action( 'wm_settings_register_pages' );
    }


    // WORDPRESS ACTIONS

    public static function activated_plugin()
    {
        $active_plugins = get_option( 'active_plugins' );
        if ( $position = array_search( self::$name, $active_plugins ) ) {
            array_splice( $active_plugins, $position, 1 );
            array_unshift( $active_plugins, self::$name );
            update_option( 'active_plugins', $active_plugins );
        }
    }

    public static function plugins_loaded()
    {
        load_plugin_textdomain( self::$name, false, self::$path . 'languages/');
    }

    // Page scripts and styles
    public static function admin_enqueue_scripts()
    {
        // Media upload
        wp_enqueue_media();
        // Main script
        wp_enqueue_script( self::$name, self::$url . 'js/wm-settings.js', array( 'jquery', 'wp-color-picker' ) );
        // Data
        wp_localize_script( self::$name, 'ajax', array(
            'url'     => admin_url( 'admin-ajax.php' ),
            'spinner' => admin_url( 'images/spinner.gif' )
        ) );
        // Styles
        wp_enqueue_style( self::$name, self::$url . 'css/wm-settings.css' );
        wp_enqueue_style( 'wp-color-picker' );
    }
}

?>
