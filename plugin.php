<?php
/*
Plugin Name: WebMaestro Settings
Plugin URI: http://webmaestro.fr/wordpress-settings-api-options-pages/
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Simplified options system for WordPress. Generates a default page for settings.
Version: 1.4
License: GNU General Public License
License URI: license.txt
Text Domain: wm-settings
*/


if ( ! class_exists( 'WM_Settings' ) ) {


    // USER FUNCTIONS

    // Get setting value
    function get_setting( $id, $name = null )
    {
        // Store values
        static $values = array();

        if ( ! array_key_exists( $id, $values ) ) {
            $values[$id] = get_option( "wm_settings_{$id}" );
        }
        if ( empty( $values[$id] ) ) {
            return null;
        }
        if ( $name ) {
            // Setting value
            return array_key_exists( $name, $values[$id] ) ? $values[$id][$name] : null;
        }
        // Section values
        return $values[$id];
    }

    // Create a new setting page
    function create_settings_page( $name = 'custom_settings', $title = null, $menu = array(), $settings = array(), array $config = array() )
    {
        return new WM_Settings( $name, $title, $menu, $settings, $config );
    }


    // PLUGIN CLASS

    class WM_Settings {

        private $name,           // Page id
            $title,              // Page title
            $menu,               // Menu configuration
            $config,             // Page configuration
            $sections = array(), // Settings list
            $empty = true,       // Page status
            $notices;            // Page notices

        private static $instances = array(); // Page instances


        // CONSTRUCTOR

        public function __construct( $name = 'custom_settings', $title = null, $menu = array(), $settings = array(), array $config = array() )
        {
            $this->name = (string) $name;
            $this->title = $title ? (string) $title : __( 'Custom Settings', 'wm-settings' );
            $this->menu = is_array( $menu ) ? array_merge( array(
                'parent'     => 'themes.php',     // Parent page id
                'title'      => $this->title,     // Menu item title
                'capability' => 'manage_options', // User capability to access
                'icon_url'   => null,             // Menu item icon (for parent page only)
                'position'   => null              // Menu item priority
            ), $menu ) : false;
            $this->config  = array_merge( array(
                'description' => null,                                  // Page description
                'submit'      => __( 'Save Settings', 'wm-settings' ),  // Submit button text
                'reset'       => __( 'Reset Settings', 'wm-settings' ), // Reset button text (false to disable)
                'tabs'        => true,                                  // Use tabs to switch sections
                'updated'     => null                                   // Custom success message
            ), $config );

            // Get cached notices
            $this->notices = array_filter( (array) get_transient( "wm_settings_{$this->name}_notices" ) );

            // Register user defined settings
            $this->apply_settings( $settings );

            // Record this instance
            self::$instances[$this->name] = $this;

            // Admin menu hook
            add_action( 'admin_init', array( $this, 'admin_init' ), 101 );
        }


        // USER METHODS

        // Register settings callbacks
        public function apply_settings( $settings )
        {
            $filter = is_callable( $settings )
                ? $settings
                : function ( array $s ) use ( $settings ) {
                    return array_merge( $s, array_filter( (array) $settings ) );
                };
            add_filter( "wm_settings_{$this->name}", $filter );
        }

        // Register alert message
        public function add_notice( $message, $type = 'info' )
        {
            $this->notices[] = array(
                'type'    => $type,
                'message' => $message,
                'setting' => $this->name,
                'code'    => "{$type}_notice"
            );
            // Cache notices (in case page is not to be displayed yet)
            set_transient( "wm_settings_{$this->name}_notices", $this->notices );
        }

        public function get_defaults( $section_id )
        {
            return $this->sanitize_setting( array(
                'wm_settings_defaults' => true
            ), $section_id );
        }


        // WORDPRESS ACTIONS

        // Register menu items
        public static function admin_menu()
        {
            // Public hook to register pages
            do_action( 'wm_settings_register_pages' );
            // Add each instance menu page
            foreach ( self::$instances as $page ) {
                if ( $page->menu ) {
                    if ( $page->menu['parent'] ) {
                        // As child item
                        $hookname = add_submenu_page( $page->menu['parent'], $page->title, $page->menu['title'], $page->menu['capability'], $page->name, array( $page, 'do_page' ) );
                    } else {
                        // As parent item
                        $hookname = add_menu_page( $page->title, $page->menu['title'], $page->menu['capability'], $page->name, array( $page, 'do_page' ), $page->menu['icon_url'], $page->menu['position'] );
                        if ( $page->title !== $page->menu['title'] ) {
                            // "Main" child item
                            add_submenu_page( $page->name, $page->title, $page->title, $page->menu['capability'], $page->name );
                        }
                    }
                    // Enable page display
                    add_action( "load-{$hookname}", array( $page, 'load_page' ) );
                }
            }
        }

        // Register settings
        public function admin_init()
        {
            // Settings are registered through filters callbacks
            $settings = array_filter( (array) apply_filters( "wm_settings_{$this->name}", $this->sections ) );

            // Reset request
            if ( $reset = isset( $_POST["wm_settings_reset"] ) ) {
                // Prepare notice
                $this->add_notice( __( 'Default settings have been reset.', 'wm-settings' ) );
            }

            // Prepare sections
            foreach ( $settings as $id => $section ) {

                // Prefixed section id
                $setting_id = "wm_settings_{$id}";

                if ( $reset ) {
                    // Force default values
                    $_POST[$setting_id]['wm_settings_defaults'] = true;
                }

                $this->sections[$id] = array_merge( array(
                    'title'       => null,   // Section title
                    'description' => null,   // Section description
                    'fields'      => array() // Controls list
                ), (array) $section );

                // Prepare section's fields
                foreach ( array_filter( (array) $this->sections[$id]['fields'] ) as $name => $field ) {
                    // Update page status
                    $this->empty = false;

                    // Set field
                    $this->sections[$id]['fields'][$name] = array_merge( array(
                        'type'        => 'text',             // Input type
                        'label'       => is_string( $field ) // Field title
                            ? $field
                            : null,
                        'description' => null,               // Field description
                        'default'     => null,               // Default value
                        'sanitize'    => null,               // Sanitation callback
                        'attributes'  => array(),            // HTML input attributes
                        'options'     => null,               // Options list (for "radio", "select" or "multi" types)
                        'action'      => null                // Callback function (for "action" type)
                    ), (array) $field );
                }

                // Register setting
                register_setting( $this->name, $setting_id, array( $this, 'sanitize_setting' ) );
                // Register section
                add_settings_section( $setting_id, $this->sections[$id]['title'], array( $this, 'do_section' ), $this->name );

                if ( ! get_option( $setting_id ) ) {
                    // Compatibility < v1.4
                    if ( $old_option = get_option( $id ) ) {
                        add_option( $setting_id, $this->sanitize_setting( $old_option, $id ) );
                    } else {
                        // Initialise option with default values
                        add_option( $setting_id, $this->get_defaults( $id ) );
                    }
                }

                // Register controls
                foreach ( $this->sections[$id]['fields'] as $name => $field ) {
                    $field = array_merge( array(
                        'id'        => "{$id}_{$name}",
                        'name'      => "{$setting_id}[{$name}]",
                        'value'     => get_setting( $id, $name ),
                        'label_for' => $field['label'] === false
                            ? 'hidden'
                            : $name
                    ), $field );
                    add_settings_field( $field['id'], $field['label'], array( __CLASS__, 'do_field' ), $this->name, $setting_id, $field );

                    // Register callback for "action" field type
                    if ( $field['type'] === 'action' && is_callable( $field['action'] ) ) {
                        add_action( "wp_ajax_{$field['id']}", $field['action'] );
                    }
                }
            }
        }

        // Load page
        public function load_page()
        {
            // Update callback
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
                do_action( "{$this->name}_settings_updated" );
            }

            // Notices
            global $wp_settings_errors;
            foreach ( $this->notices + array_filter( (array) get_transient( 'settings_errors' ) ) as $notice ) {
                // Custom update message
                if ( $notice['code'] === 'settings_updated' && $this->config['updated'] !== null && $notice['setting'] === 'general' ) {
                    if ( $this->config['updated'] ) {
                        $notice['message'] = (string) $this->config['updated'];
                    } else {
                        // Disable update message
                        continue;
                    }
                }
                // Avoid duplicates
                if ( ! in_array( $notice, (array) $wp_settings_errors ) ) {
                    // Add to global
                    $wp_settings_errors[] = $notice;
                }
            }
            // Delete cached notices
            delete_transient( "wm_settings_{$this->name}_notices" );
            delete_transient( 'settings_errors' );

            // Enqueue page scripts
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
        }

        // Page scripts and styles
        public static function admin_enqueue_scripts()
        {
            // Media upload
            wp_enqueue_media();
            // Main script
            wp_enqueue_script( 'wm-settings', plugins_url( 'wm-settings.js' , __FILE__ ), array( 'jquery', 'wp-color-picker' ) );
            // Data
            wp_localize_script( 'wm-settings', 'ajax', array(
                'url' => admin_url( 'admin-ajax.php' ),
                'spinner' => admin_url( 'images/spinner.gif' )
            ) );
            // Styles
            wp_enqueue_style( 'wm-settings', plugins_url( 'wm-settings.css' , __FILE__ ) );
            wp_enqueue_style( 'wp-color-picker' );
        }


        // SETTINGS API CALLBACKS

        // Page display callback
        public function do_page()
        { ?>
            <form action="options.php" method="POST" enctype="multipart/form-data" class="wrap">
                <h2><?php echo $this->title; ?></h2>
                <?php
                    // Display notices
                    settings_errors();
                    if ( $text = $this->config['description'] ) {
                        echo wpautop( $text );
                    }
                    // Display sections
                    do_settings_sections( $this->name );
                    if ( ! $this->empty ) {
                        settings_fields( $this->name );
                        // Tabs
                        if ( $this->config['tabs'] && count( $this->sections ) > 1 ) {
                            echo "<div class=\"wm-settings-tabs\"></div>";
                        }
                        // Submit button
                        submit_button( $this->config['submit'], 'large primary' );
                        // Reset button
                        if ( $this->config['reset'] ) {
                            $confirm = esc_js( __( 'Do you really want to reset these settings to their default values ?', 'wm-settings' ) );
                            submit_button( $this->config['reset'], 'small', "wm_settings_reset", true, array(
                                'onclick' => "return confirm('{$confirm}');"
                            ) );
                        }
                    }
                ?>
            </form>
        <?php }

        // Section display callback
        public function do_section( $args )
        {
            extract( $args );
            $section_id = preg_replace( '/^wm_settings_/', '', $id );
            echo "<input name=\"{$id}[wm_settings_id]\" type=\"hidden\" value=\"{$section_id}\" class=\"wm-settings-section\" />";
            if ( $text = $this->sections[$section_id]['description'] ) {
                echo wpautop( $text );
            }
        }

        // Control display callback
        public static function do_field( $args )
        {
            extract( $args );
            // HTML attributes
            $attrs = "name=\"{$name}\"";
            foreach ( $attributes as $k => $v ) {
                $k = sanitize_key( $k );
                $v = esc_attr( $v );
                $attrs .= " {$k}='{$v}'";
            }
            // Field description "paragraphed"
            $desc = $description ? "<p class=\"description\">{$description}</p>" : '';
            // Display control
            switch ( $type )
            {
                // Checkbox
                case 'checkbox':
                    $check = checked( 1, $value, false );
                    echo "<label><input {$attrs} id=\"{$id}\" type=\"checkbox\" value=\"1\" {$check} />";
                    if ( $description ) {
                        echo " {$description}";
                    }
                    echo "</label>";
                    break;
                // Radio options
                case 'radio':
                    if ( ! is_array( $options ) ) {
                        _e( 'No options defined.', 'wm-settings' );
                    } else {
                        echo "<fieldset id=\"{$id}\">";
                        foreach ( $options as $v => $label ) {
                            $check = checked( $v, $value, false );
                            $options[$v] = "<label><input {$attrs} type=\"radio\" value=\"{$v}\" {$check} /> {$label}</label>";
                        }
                        echo implode( '<br />', $options );
                        echo "{$desc}</fieldset>";
                    }
                    break;
                // Select options
                case 'select':
                    if ( ! is_array( $options ) ) {
                        _e( 'No options defined.', 'wm-settings' );
                    } else {
                        echo "<select {$attrs} id=\"{$id}\">";
                        foreach ( $options as $v => $label ) {
                            $select = selected( $v, $value, false );
                            echo "<option value=\"{$v}\" {$select} />{$label}</option>";
                        }
                        echo "</select>{$desc}";
                    }
                    break;
                // Media upload button
                case 'media':
                    echo "<fieldset class=\"wm-settings-media\" id=\"{$id}\"><input {$attrs} type=\"hidden\" value=\"{$value}\" />";
                    $select_text = sprintf( __( 'Select %s', 'wm-settings' ), $label );
                    echo "<p><a class='button button-large wm-select-media' title=\"{$label}\">{$select_text}</a> ";
                    $remove_text = sprintf( __( 'Remove %s', 'wm-settings' ), $label );
                    echo "<a class='button button-small wm-remove-media' title=\"{$label}\">{$remove_text}</a></p>";
                    if ( $value ) {
                        echo wpautop( wp_get_attachment_image( $value, 'medium' ) );
                    }
                    echo "{$desc}</fieldset>";
                    break;
                // Text bloc
                case 'textarea':
                    echo "<textarea {$attrs} id=\"{$id}\" class=\"large-text\">{$value}</textarea>{$desc}";
                    break;
                // Multiple checkboxes
                case 'multi':
                    if ( ! is_array( $options ) ) {
                        _e( 'No options defined.', 'wm-settings' );
                    } else {
                        echo "<fieldset id=\"{$id}\">";
                        foreach ( $options as $n => $label ) {
                            $a = preg_replace( "/name\=\"(.+)\"/", "name='$1[{$n}]'", $attrs );
                            $check = checked( 1, $value[$n], false );
                            $options[$n] = "<label><input {$a} type=\"checkbox\" value=\"1\" {$check} /> {$label}</label>";
                        }
                        echo implode( '<br />', $options );
                        echo "{$desc}</fieldset>";
                    }
                    break;
                // Ajax action button
                case 'action':
                    if ( ! is_callable( $action ) ) {
                        _e( 'No action defined.', 'wm-settings' );
                    }
                    echo "<p class=\"wm-settings-action\"><input {$attrs} id=\"{$id}\" type=\"button\" class=\"button button-large\" value=\"{$label}\" /></p>{$desc}";
                    break;
                // Color picker
                case 'color':
                    $v = esc_attr( $value );
                    echo "<input {$attrs} id=\"{$id}\" type=\"text\" value=\"{$v}\" class=\"wm-settings-color\" />{$desc}";
                    break;
                // HTML5 input ("text", "email"...)
                default:
                    $v = esc_attr( $value );
                    echo "<input {$attrs} id=\"{$id}\" type=\"{$type}\" value=\"{$v}\" class=\"regular-text\" />{$desc}";
                    break;
            }
        }

        // Sanitize values before save
        public function sanitize_setting( array $inputs, $id = null )
        {
            if ( ! $id ) {
                if ( ! isset( $inputs["wm_settings_id"] ) ) {
                    return $inputs;
                }
                // Get id from hidden section input
                $id = $inputs["wm_settings_id"];
            }

            $defaults = isset( $inputs["wm_settings_defaults"] );

            // Array of sanitized values
            $values = array();

            // Section's fields
            foreach ( $this->sections[$id]['fields'] as $name => $field ) {

                if ( $defaults ) {
                    // Set default as input
                    $input = $field['default'];
                } else {
                    // Set posted input
                    $input = isset( $inputs[$name] )
                        ? $inputs[$name]
                        : null;
                }

                // "Custom" sanitation
                if ( $field['sanitize'] ) {
                    $values[$name] = call_user_func( $field['sanitize'], $input, $name );
                } else {

                    // "Default" sanitation
                    switch ( $field['type'] )
                    {
                        case 'checkbox':
                            // Boolean
                            $values[$name] = $input ? 1 : 0;
                            break;

                        case 'radio':
                        case 'select':
                            // Field option key
                            $values[$name] = sanitize_key( $input );
                            break;

                        case 'media':
                            // Attachment id
                            $values[$name] = absint( $input );
                            break;

                        case 'color':
                            // Hex color
                            $values[$name] = preg_match( '/^#[a-f0-9]{6}$/', $input ) ? $input : "#808080";
                            break;

                        case 'textarea':
                            $text = "";
                            // Convert and keep new lines and tabulations
                            $nl = "WM-SETTINGS-NEW-LINE";
                            $tb = "WM-SETTINGS-TABULATION";
                            $lines = explode( $nl, sanitize_text_field( str_replace( "\t", $tb, str_replace( "\n", $nl, $input ) ) ) );
                            foreach ( $lines as $line ) {
                                $text .= str_replace( $tb, "\t", trim( $line ) ) . "\n";
                            }
                            $values[$name] = trim( $text );
                            break;

                        case 'multi':
                            $values[$name] = array();
                            foreach ( $field['options'] as $n => $opt ) {
                                $values[$name][$n] = empty( $input[$n] ) ? 0 : 1;
                            }
                            break;

                        case 'action':
                            break;

                        case 'email':
                            $values[$name] = sanitize_email( $input );
                            break;

                        case 'url':
                            $values[$name] = esc_url_raw( $input );
                            break;

                        case 'number':
                            $values[$name] = floatval( $input );
                            break;

                        default:
                            $values[$name] = sanitize_text_field( $input );
                            break;
                    }
                }
            }
            return $values;
        }


        // This plugin may be used by other ones, so let's try to load it first
        public static function plugin_priority()
        {
            $wm_settings = plugin_basename( __FILE__ );
            $active_plugins = get_option( 'active_plugins' );
            if ( $position = array_search( $wm_settings, $active_plugins ) ) {
                array_splice( $active_plugins, $position, 1 );
                array_unshift( $active_plugins, $wm_settings );
                update_option( 'active_plugins', $active_plugins );
            }
        }
    }
    add_action( 'activated_plugin', array( 'WM_Settings', 'plugin_priority' ) );
    add_action( 'admin_menu', array( 'WM_Settings', 'admin_menu' ) );
}

?>
