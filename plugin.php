<?php
/*
Plugin Name: WebMaestro Settings
Plugin URI: http://webmaestro.fr/wordpress-settings-api-options-pages/
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Simplified options system for WordPress. Generates a default page for settings.
Version: 1.3.1
License: GNU General Public License
License URI: license.txt
Text Domain: wm-settings
GitHub Plugin URI: https://github.com/WebMaestroFr/wm-settings
GitHub Branch: master
*/

if ( ! class_exists( 'WM_Settings' ) ) { // Do not redeclare

    class WM_Settings { // Plugin class

        private $page,           // Page unique id
            $title,              // Page title
            $menu,               // Menu configuration
            $settings = array(), // Variables & controls list
            $empty = true,       // Page status
            $notices = array();  // Alert messages

        // Create new options page
        public function __construct( $page = 'custom_settings', $title = null, $menu = array(), array $settings = array(), array $args = array() )
        {
            $this->page = (string) $page;
            $this->title = $title ? (string) $title : __( 'Custom Settings', 'wm-settings' );
            $this->menu = is_array( $menu ) ? array_merge( array(
                'parent'     => 'themes.php',     // Parent page id
                'title'      => $this->title,     // Menu item title
                'capability' => 'manage_options', // User capability to access
                'icon_url'   => null,             // Menu item icon (for parent page only)
                'position'   => null              // Menu item priority
            ), $menu ) : false;
            $this->apply_settings( $settings );
            $this->args  = array_merge( array(
                'description' => null,                                  // Page description
                'submit'      => __( 'Save Settings', 'wm-settings' ),  // Submit button text
                'reset'       => __( 'Reset Settings', 'wm-settings' ), // Reset button text (false to disable)
                'tabs'        => $this->menu && $this->menu['parent'],  // Use tabs to switch sections
                'updated'     => null                                   // Custom success message
            ), $args ); // Page configuration
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'admin_init', array( $this, 'admin_init' ), 101 );
        }


        // USER METHODS

        // Add new section & fields
        public function apply_settings( array $settings )
        {
            foreach ( $settings as $setting => $section ) {
                $section = array_merge( array(
                    'title'       => null,   // Section title
                    'description' => null,   // Section description
                    'fields'      => array() // Controls list
                ), $section );
                foreach ( $section['fields'] as $name => $field ) {
                    $section['fields'][$name] = array_merge( array(
                        'type'        => 'text',  // Input type
                        'label'       => null,    // Field title
                        'description' => null,    // Field description
                        'default'     => null,    // Default value
                        'sanitize'    => null,    // Sanitation callback
                        'attributes'  => array(), // HTML input attributes
                        'options'     => null,    // Options list (for "radio", "select" or "multi" types)
                        'action'      => null     // Callback function (for "action" type)
                    ), $field );
                }
                $this->settings[$setting] = $section;
                if ( ! get_option( $setting ) ) {
                    // Register default values
                    add_option( $setting, $this->get_defaults( $setting ) );
                }
            }
        }

        // Register alert message
        public function add_notice( $message, $type = 'info' )
        {
            $this->notices[] = array(
                'message' => (string) $message,
                'type'    => (string) $type
            );
        }


        // WORDPRESS ACTIONS

        // Register menu item
        public function admin_menu()
        {
            if ( $this->menu ) {
                if ( $this->menu['parent'] ) {
                    // As child item
                    $page = add_submenu_page( $this->menu['parent'], $this->title, $this->menu['title'], $this->menu['capability'], $this->page, array( $this, 'do_page' ) );
                } else {
                    // As parent item
                    $page = add_menu_page( $this->title, $this->menu['title'], $this->menu['capability'], $this->page, array( $this, 'do_page' ), $this->menu['icon_url'], $this->menu['position'] );
                    if ( $this->title !== $this->menu['title'] ) {
                        // "Main" child item
                        add_submenu_page( $this->page, $this->title, $this->title, $this->menu['capability'], $this->page );
                    }
                }
                // Enable page display
                add_action( 'load-' . $page, array( $this, 'load_page' ) );
            }
        }

        // Register settings
        public function admin_init()
        {
            foreach ( $this->settings as $setting => $section ) {
                // Register section
                register_setting( $this->page, $setting, array( $this, 'sanitize_setting' ) );
                add_settings_section( $setting, $section['title'], array( $this, 'do_section' ), $this->page );
                if ( ! empty( $section['fields'] ) ) {
                    // Update page status
                    $this->empty = false;
                    // Get section fields values
                    $values = get_setting( $setting );
                    // Register controls
                    foreach ( $section['fields'] as $name => $field ) {
                        $id = $setting . '_' . $name;
                        $field = array_merge( array(
                            'id'        => $id,
                            'name'      => $setting . '[' . $name . ']',
                            'value'     => isset( $field['value'] )
                                ? $field['value']
                                : isset( $values[$name] )
                                    ? $values[$name]
                                    : null,
                            'label_for' => $field['label'] === false
                                ? 'hidden'
                                : $id
                        ), $field );
                        add_settings_field( $name, $field['label'], array( __CLASS__, 'do_field' ), $this->page, $setting, $field );
                        if ( $field['type'] === 'action' && is_callable( $field['action'] ) ) {
                            // Register callback for "action" field type
                            add_action( "wp_ajax_{$setting}_{$name}", $field['action'] );
                        }
                    }
                }
            }
            if ( isset( $_POST["{$this->page}_reset"] ) ) {
                // Reset settings to default values
                $this->reset();
            }
        }

        // Load page
        public function load_page()
        {
            global $wp_settings_errors;
            // Display registered notices
            foreach ( $this->notices as $notice ) {
                $wp_settings_errors[] = array_merge( $notice, array(
                    'setting' => $this->page,
                    'code'    => $notice['type'] . '_notice'
                ) );
            }
            // On update
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
                // Custom success notice
                if ( $this->args['updated'] !== null && $notices = get_transient( 'settings_errors' ) ) {
                    delete_transient( 'settings_errors' );
                    foreach ( $notices as $i => $notice ) {
                        if ( $notice['setting'] === 'general' && $notice['code'] === 'settings_updated' ) {
                            if ( $this->args['updated'] ) {
                                // Replace update message
                                $notice['message'] = (string) $this->args['updated'];
                            } else {
                                // Disable update message
                                continue;
                            }
                        }
                        $wp_settings_errors[] = $notice;
                    }
                }
                // Callback action
                do_action( "{$this->page}_settings_updated" );
            }
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

        // Get filtered default values
        private function get_defaults( $setting )
        {
            $defaults = array();
            foreach ( $this->settings[$setting]['fields'] as $name => $field ) {
                if ( $field['default'] !== null ) {
                    $defaults[$name] = $field['default'];
                }
            }
            return $defaults;
        }

        // Reset settings to default values
        private function reset()
        {
            foreach ( $this->settings as $setting => $section ) {
                $_POST[$setting] = array_merge( $_POST[$setting], $this->get_defaults( $setting ) );
            }
            add_settings_error( $this->page, 'settings_reset', __( 'Default settings have been reset.', 'wm-settings' ), 'updated' );
        }

        public function do_page()
        { ?>
            <form action="options.php" method="POST" enctype="multipart/form-data" class="wrap">
                <h2><?php echo $this->title; ?></h2>
                <?php
                    // Display notices
                    settings_errors();
                    if ( $text = $this->args['description'] ) {
                        echo wpautop( $text );
                    }
                    // Display sections
                    do_settings_sections( $this->page );
                    if ( ! $this->empty ) {
                        settings_fields( $this->page );
                        // Set tabs
                        if ( $this->args['tabs'] && count( $this->settings ) > 1 ) { ?>
                            <div class="wm-settings-tabs"></div>
                        <?php }
                        // Display submit
                        submit_button( $this->args['submit'], 'large primary' );
                        if ( $this->args['reset'] ) {
                            // Display reset
                            submit_button( $this->args['reset'], 'small', "{$this->page}_reset", true, array( 'onclick' => "return confirm('" . __( 'Do you really want to reset all these settings to their default values ?', 'wm-settings' ) . "');" ) );
                        }
                    }
                ?>
            </form>
        <?php }

        // Section display callback
        public function do_section( $args )
        {
            extract( $args );
            echo "<input name='{$id}[{$this->page}_setting]' type='hidden' value='{$id}' class='wm-settings-section' />";
            if ( $text = $this->settings[$id]['description'] ) {
                echo wpautop( $text );
            }
        }

        // Control display callback
        public static function do_field( $args )
        {
            extract( $args );
            // HTML attributes
            $attrs = "name='{$name}'";
            foreach ( $attributes as $k => $v ) {
                $k = sanitize_key( $k );
                $v = esc_attr( $v );
                $attrs .= " {$k}='{$v}'";
            }
            // Field description "paragraphed"
            $desc = $description ? "<p class='description'>{$description}</p>" : '';
            // Display control
            switch ( $type )
            {
                // Checkbox
                case 'checkbox':
                    $check = checked( 1, $value, false );
                    echo "<label><input {$attrs} id='{$id}' type='checkbox' value='1' {$check} />";
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
                        echo "<fieldset id='{$id}'>";
                        foreach ( $options as $v => $label ) {
                            $check = checked( $v, $value, false );
                            $options[$v] = "<label><input {$attrs} type='radio' value='{$v}' {$check} /> {$label}</label>";
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
                        echo "<select {$attrs} id='{$id}'>";
                        foreach ( $options as $v => $label ) {
                            $select = selected( $v, $value, false );
                            echo "<option value='{$v}' {$select} />{$label}</option>";
                        }
                        echo "</select>{$desc}";
                    }
                    break;
                // Media upload button
                case 'media':
                    echo "<fieldset class='wm-settings-media' id='{$id}'><input {$attrs} type='hidden' value='{$value}' />";
                    echo "<p><a class='button button-large wm-select-media' title='{$label}'>" . sprintf( __( 'Select %s', 'wm-settings' ), $label ) . "</a> ";
                    echo "<a class='button button-small wm-remove-media' title='{$label}'>" . sprintf( __( 'Remove %s', 'wm-settings' ), $label ) . "</a></p>";
                    if ( $value ) {
                        echo wpautop( wp_get_attachment_image( $value, 'medium' ) );
                    }
                    echo "{$desc}</fieldset>";
                    break;
                // Text bloc
                case 'textarea':
                    echo "<textarea {$attrs} id='{$id}' class='large-text'>{$value}</textarea>{$desc}";
                    break;
                // Multiple checkboxes
                case 'multi':
                    if ( ! is_array( $options ) ) {
                        _e( 'No options defined.', 'wm-settings' );
                    } else {
                        echo "<fieldset id='{$id}'>";
                        foreach ( $options as $n => $label ) {
                            $a = preg_replace( "/name\=\'(.+)\'/", "name='$1[{$n}]'", $attrs );
                            $check = checked( 1, $value[$n], false );
                            $options[$n] = "<label><input {$a} type='checkbox' value='1' {$check} /> {$label}</label>";
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
                    echo "<p class='wm-settings-action'><input {$attrs} id='{$id}' type='button' class='button button-large' value='{$label}' /></p>{$desc}";
                    break;
                // Color picker
                case 'color':
                    $v = esc_attr( $value );
                    echo "<input {$attrs} id='{$id}' type='text' value='{$v}' class='wm-settings-color' />{$desc}";
                    break;
                // HTML5 input ("text", "email"...)
                default:
                    $v = esc_attr( $value );
                    echo "<input {$attrs} id='{$id}' type='{$type}' value='{$v}' class='regular-text' />{$desc}";
                    break;
            }
        }

        public function sanitize_setting( $inputs )
        {
            if ( empty( $inputs["{$this->page}_setting"] ) ) {
                return $inputs;
            }
            $values = array();
            // Section id
            $setting = $inputs["{$this->page}_setting"];
            foreach ( $this->settings[$setting]['fields'] as $name => $field ) {
                // Field value
                $input = array_key_exists( $name, $inputs ) ? $inputs[$name] : null;
                if ( $field['sanitize'] ) {
                    // Custom sanitation
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
                            $values[$name] = preg_match( '/^#[a-f0-9]{6}$/i', $input ) ? $input : '#808080';
                            break;

                        case 'textarea':
                            $text = '';
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
                            if ( ! $input || empty( $field['options'] ) ) {
                                // Nothing checked
                                break;
                            }
                            foreach ( $field['options'] as $n => $opt ) {
                                $input[$n] = empty( $input[$n] ) ? 0 : 1;
                            }
                            $values[$name] = json_encode( $input );
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

        public static function parse_multi( $result )
        {
            // Check if the result was recorded as JSON, and if so, returns an array instead
            return ( is_string( $result ) && $array = json_decode( $result, true ) ) ? $array : $result;
        }

        public static function plugin_priority()
        {
            // This plugin may be used by other ones, so let's load it first
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


    // USER FUNCTIONS

    // Get setting value
    function get_setting( $setting, $option = false )
    {
        $setting = get_option( $setting );
        if ( is_array( $setting ) ) {
            if ( $option ) {
                // Setting value
                return isset( $setting[$option] ) ? WM_Settings::parse_multi( $setting[$option] ) : false;
            }
            foreach ( $setting as $k => $v ) {
                $setting[$k] = WM_Settings::parse_multi( $v );
            }
            // Section values
            return $setting;
        }
        return $option ? false : $setting;
    }

    // Create a new setting page
    function create_settings_page( $page = 'custom_settings', $title = null, $menu = array(), $settings = array(), $args = array() )
    {
        return new WM_Settings( $page, $title, $menu, $settings, $args );
    }

}

?>
