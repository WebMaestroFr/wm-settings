<?php
/*
Plugin Name: WebMaestro Settings
Plugin URI: http://webmaestro.fr/wordpress-settings-api-options-pages/
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Simplified options system for WordPress. Generates a default page for settings.
Version: 1.5
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

    function create_customize_section( $name = 'custom_section', $title = null, $fields = array(), $description = null )
    {
        return new WM_Settings( "customize_{$name}", $title, false, array(
            $name => array(
                'title'       => $title,
                'description' => $description,
                'fields'      => $fields,
                'customize'   => true
            )
        ) );
    }


    // PLUGIN CLASS

    class WM_Settings {

        private $name,           // Page id
            $title,              // Page title
            $menu,               // Menu configuration
            $config,             // Page configuration
            $settings = array(), // User defined settings
            $sections = array(), // Settings list
            $empty = true,       // Page status
            $notices;            // Page notices

        private static $instances = array(), // Page instances
            $actions = array(),              // Registered actions
            $alerts = array();               // Global notices


        // PAGE CONSTRUCTOR

        public function __construct( $name = 'custom_settings', $title = null, $menu = array(), $settings = array(), array $config = array() )
        {
            $this->name = (string) $name;
            $this->title = $title ? (string) $title : __( 'Custom Settings', 'wm-settings' );
            $this->menu = ( $menu || is_array( $menu ) ) ? array(
                'parent'     => 'options-general.php', // Parent page id
                'title'      => $this->title,          // Menu item title
                'capability' => 'manage_options',      // User capability to access
                'icon_url'   => null,                  // Menu item icon (for parent page only)
                'position'   => null                   // Menu item priority
            ) : false;
            if ( is_array( $menu ) ) {
                $this->menu = array_merge( $this->menu, $menu );
            }
            $this->config  = array_merge( array(
                'description' => null,                                  // Page description
                'submit'      => __( 'Save Settings', 'wm-settings' ),  // Submit button text
                'reset'       => __( 'Reset Settings', 'wm-settings' ), // Reset button text (false to disable)
                'tabs'        => true,                                  // Use tabs to switch sections
                'updated'     => __( 'Settings saved.', 'wm-settings')  // Custom success message
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
            if ( is_callable( $settings ) ) {
                add_filter( "wm_settings_{$this->name}", $settings );
            } else {
                $this->settings += (array) $settings;
            }
        }

        // Get default values
        public function get_defaults( $section_id )
        {
            return $this->sanitize_setting( array(
                'wm_settings_defaults' => true
            ), $section_id );
        }

        // Register page notice
        public function add_notice( $message, $type = 'info', $title = null, $backtrace = false )
        {
            $this->notices[] = array(
                'type'    => $type,
                'message' => self::get_notice_message( $message, $title, $backtrace ),
                'setting' => $this->name,
                'code'    => "{$type}_notice"
            );
            // Cache notices until they're shown
            set_transient( "wm_settings_{$this->name}_notices", $this->notices );
        }

        // Register global alert
        public static function add_alert( $message, $type = 'error', $title = null, $backtrace = false )
        {
            self::$alerts[] = array(
                'type'      => $type,
                'message'   => self::get_notice_message( $message, $title, $backtrace )
            );
            // Cache alerts until they're shown
            set_transient( 'wm_settings_alerts', self::$alerts );
        }

        private static function get_notice_message( $message, $title, $backtrace )
        {
            $message = $title ? "<strong>{$title}</strong><br />{$message}" : $message;
            if ( $backtrace && $stack = array_slice( debug_backtrace(), 2 ) ) {
                if ( true === $backtrace ) {
                    $message .= "<ol start=\"0\">";
                    foreach ( $stack as $i => $backtrace ) {
                        $message .= '<li>' . self::get_backtrace( $backtrace ) . '</li>';
                    }
                    $message .= "</ol>";
                } else if ( ! empty( $stack[$backtrace] ) ) {
                    $message .= self::get_backtrace( $stack[$backtrace] );
                }
            }
            return $message;
        }

        private static function get_backtrace( $backtrace )
        {
            $output = "<pre>";
            if ( ! empty( $backtrace['class'] ) ) {
                $output .= "<strong>{$backtrace['class']}</strong>";
                if ( ! empty( $backtrace['type'] ) ) {
                    $output .= $backtrace['type'];
                }
            }
            if ( ! empty( $backtrace['function'] ) ) {
                $output .= "<strong>{$backtrace['function']}</strong>(";
                if ( ! empty( $backtrace['args'] ) ) {
                    $args = implode( ', ', array_map( function ( $arg ) {
                        if ( is_scalar( $arg ) ) {
                            return var_export( $arg, true );
                        }
                        $type = gettype( $arg );
                        return "<em>{$type}</em>";
                    }, $backtrace['args'] ) );
                    $output .= " {$args} ";
                }
                $output .= ");\n";
            }
            if ( ! empty( $backtrace['file'] ) ) {
                $output .= "In <strong>{$backtrace['file']}</strong>";
                if ( ! empty( $backtrace['line'] ) ) {
                    $output .= " on line <strong>{$backtrace['line']}</strong>";
                }
            }
            return $output . "</pre>";
        }


        // WORDPRESS ACTIONS

        // Register Ajax Actions
        public static function init()
        {
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX && $actions = array_filter( (array) get_transient( 'wm_settings_actions' ) ) ) {
                foreach ( $actions as $field_id => $action ) {
                    add_action( "wp_ajax_{$field_id}", $action );
                }
            }
            self::$alerts = array_filter( (array) get_transient( 'wm_settings_alerts' ) );
        }

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
            // Reset request
            if ( $reset = isset( $_POST["wm_settings_reset"] ) ) {
                // Prepare notice
                $this->add_notice( __( 'Settings have been reset to their default values.', 'wm-settings' ) );
            }

            // Prepare sections
            foreach ( $this->set_sections() as $id => $section ) {

                // Prefixed section id
                $setting_id = "wm_settings_{$id}";

                if ( $reset ) {
                    // Force default values
                    $_POST[$setting_id]['wm_settings_defaults'] = true;
                }

                // Register setting
                register_setting( $this->name, $setting_id, array( $this, 'sanitize_setting' ) );
                // Register section
                add_settings_section( $setting_id, $section['title'], array( $this, 'do_section' ), $this->name );

                if ( ! get_option( $setting_id ) ) {
                    // Compatibility < v1.4
                    if ( $old_option = get_option( $id ) ) {
                        add_option( $setting_id, $this->sanitize_setting( $old_option, $id ) );
                    } else {
                        // Initialise option with default values
                        add_option( $setting_id, $this->get_defaults( $id ) );
                    }
                }

                // Register fields
                foreach ( $section['fields'] as $name => $field ) {
                    $field = array_merge( $field, array(
                        'id'        => "{$id}_{$name}",
                        'name'      => "{$setting_id}[{$name}]",
                        'value'     => get_setting( $id, $name ),
                        'label_for' => $field['label'] === false
                            ? 'hidden'
                            : $name
                    ) );
                    add_settings_field( $field['id'], $field['label'], array( __CLASS__, 'do_field' ), $this->name, $setting_id, $field );

                    // Update page status
                    $this->empty = false;

                    // Register callback for "action" field type
                    if ( $field['type'] === 'action' && is_callable( $field['action'] ) ) {
                        self::$actions[$field['id']] = $field['action'];
                    }
                }
            }
        }

        // Prepare sections
        private function set_sections()
        {
            // Settings are registered through filters callbacks
            $settings = array_filter( (array) apply_filters( "wm_settings_{$this->name}", $this->settings ) );

            foreach ( $settings as $id => $section ) {
                $this->sections[$id] = array_merge( array(
                    'title'       => null,    // Section title
                    'description' => null,    // Section description
                    'fields'      => array(), // Controls list
                    'customize'   => false
                ), (array) $section );

                // Prepare section's fields
                foreach ( array_filter( (array) $this->sections[$id]['fields'] ) as $name => $field ) {

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
                        'choices'     => null,               // Options list (for "radio", "select" or "multi" types)
                        'action'      => null                // Callback function (for "action" type)
                    ), (array) $field );

                    // Compatibility < v1.5
                    if ( null === $this->sections[$id]['fields'][$name]['choices'] && ! empty( $field['options'] ) ) {
                        $this->sections[$id]['fields'][$name]['choices'] = $field['options'];
                    }
                }
            }

            return $this->sections;
        }

        // Display global notices
        public static function admin_notices()
        {
            foreach ( array_map( 'unserialize', array_unique( array_map( 'serialize', self::$alerts ) ) ) as $alert ) {
                echo "<div class=\"wm-settings-alert notice {$alert['type']}\"><p>{$alert['message']}</p></div>";
            }
            // Delete cached alerts
            delete_transient( 'wm_settings_alerts' );
        }

        // Load page
        public function load_page()
        {
            // Update callback
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
                do_action( "{$this->name}_settings_updated" );
            }

            // Record actions
            set_transient( 'wm_settings_actions', self::$actions );

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

        public static function customize_register( $wp_customize )
        {
            // Public hook to register pages
            do_action( 'wm_settings_register_pages' );

            foreach ( self::$instances as $page ) {

                foreach ( $page->set_sections() as $id => $section ) {
                    if ( $section['customize'] ) {
                        $wp_customize->add_section( $id , $section );

                        foreach ( $section['fields'] as $name => $field ) {
                        	$wp_customize->add_setting( "wm_settings_{$id}[{$name}]", array(
                				'default'              => $field['default'],
                                'type'                 => 'option',
                    			// 'transport'            => 'refresh',
                                // 'sanitize_js_callback' => 'esc_js',
                                'sanitize_callback'    => function ( $input ) use ( $page, $id, $name ) {
                                    return $page->sanitize_setting( array(
                                        $name => $input
                                    ), $id )[$name];
                                },
                                'capability'           => $page->menu['capability']
                			) );
                            $args = array_merge( $field, array(
                                'settings' => "wm_settings_{$id}[{$name}]",
                				'section'  => $id
                			) );
                            switch ( $field['type'] ) {
                                case 'color':
                                    $control = new WP_Customize_Color_Control( $wp_customize, "{$id}_{$name}", $args );
                                    break;
                                case 'upload':
                                    $control = new WP_Customize_Upload_Control( $wp_customize, "{$id}_{$name}", $args );
                                    break;
                                case 'image':
                                    $control = new WP_Customize_Image_Control( $wp_customize, "{$id}_{$name}", $args );
                                    break;
                                case 'multi':
                                case 'action':
                                case 'media':
                                    self::add_alert( sprintf( __( 'Sorry but "<strong>%s</strong>" is not a valid <em>Customize Control</em> type quite yet.', 'wm-settings' ), $field['type'] ), 'error', __( 'WebMaestro Settings', 'wm-settings' ) );
                                    continue;
                                default:
                                    $control = new WP_Customize_Control( $wp_customize, "{$id}_{$name}", $args );
                            }
                            $wp_customize->add_control( $control );
                        }
                    }
                }
            }
        }


        // SETTINGS API CALLBACKS

        // Page display callback
        public function do_page()
        { ?>
            <form action="options.php" method="POST" enctype="multipart/form-data" class="wrap wm-settings-form">
                <h2><?php echo $this->title; ?></h2>
                <?php
                    // Prepare notices
                    $this->set_notices();
                    // Display notices
                    settings_errors( $this->name );
                    if ( $text = $this->config['description'] ) {
                        echo wpautop( $text );
                    }
                ?>
                <div class="wm-settings-sections"><?php
                    // Tabs
                    if ( ! $this->empty && $this->config['tabs'] && count( $this->sections ) > 1 ) {
                        echo "<div class=\"wm-settings-tabs\"></div>";
                    }
                    // Display sections
                    do_settings_sections( $this->name );
                ?></div>
                <?php
                    if ( ! $this->empty ) { ?>
                        <p class="submit"><?php
                            // Submit button
                            submit_button( $this->config['submit'], 'large primary', 'wm_settings_submit', false );
                            // Reset button
                            if ( $this->config['reset'] ) {
                                $confirm = esc_js( __( 'Do you really want to reset all these settings to their default values ?', 'wm-settings' ) );
                                submit_button( $this->config['reset'], 'small', 'wm_settings_reset', false, array(
                                    'onclick' => "return confirm('{$confirm}');"
                                ) );
                            }
                        ?></p>
                    <?php }
                    settings_fields( $this->name );
                ?>
            </form>
        <?php }

        // Prepare notices
        private function set_notices()
        {
            global $wp_settings_errors;
            // Avoid duplicates
            $notices = array_unique( array_map( 'serialize', array_merge(
                (array) get_transient( 'settings_errors' ),
                (array) $wp_settings_errors,
                $this->notices
            ) ) );
            // Redefine global
            $wp_settings_errors = array_filter( array_map( function ( $notice ) {
                if ( ! $notice = unserialize( $notice ) ) {
                    return null;
                }
                // Custom updated
                if ( $notice['code'] === 'settings_updated' ) {
                    if ( false === $this->config['updated'] ) {
                        return null;
                    }
                    if ( $this->config['updated'] ) {
                        $notice['message'] = (string) $this->config['updated'];
                    }
                    // If null : unchanged
                }
                return $notice;
            }, $notices ) );
            // Delete cached
            delete_transient( 'settings_errors' );
            delete_transient( "wm_settings_{$this->name}_notices" );
        }

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
                    if ( ! is_array( $choices ) ) {
                        _e( 'No options defined.', 'wm-settings' );
                    } else {
                        echo "<fieldset id=\"{$id}\">";
                        foreach ( $choices as $v => $label ) {
                            $check = checked( $v, $value, false );
                            $choices[$v] = "<label><input {$attrs} type=\"radio\" value=\"{$v}\" {$check} /> {$label}</label>";
                        }
                        echo implode( '<br />', $choices );
                        echo "{$desc}</fieldset>";
                    }
                    break;
                // Select options
                case 'select':
                    if ( ! is_array( $choices ) ) {
                        _e( 'No options defined.', 'wm-settings' );
                    } else {
                        echo "<select {$attrs} id=\"{$id}\">";
                        foreach ( $choices as $v => $label ) {
                            $select = selected( $v, $value, false );
                            echo "<option value=\"{$v}\" {$select} />{$label}</option>";
                        }
                        echo "</select>{$desc}";
                    }
                    break;
                // Media upload button
                case 'media':
                case 'upload':
                case 'image':
                    $v = $value ? esc_attr( $value ) : '';
                    echo "<fieldset class=\"wm-settings-media\" data-type=\"{$type}\" id=\"{$id}\">";
                    if ( 'upload' === $type ) {
                        echo "<input {$attrs} type=\"text\" value=\"{$v}\" class=\"disabled regular-text\" />";
                    } else {
                        echo "<input {$attrs} type=\"hidden\" value=\"{$v}\" />";
                        $src = ( $value && 'media' === $type )
                            ? wp_get_attachment_image_src( $value, 'full', true )[0]
                            : $v;
                        echo wpautop( "<img class=\"wm-preview-media\" src=\"{$src}\">" );
                    }
                    $select_text = sprintf( __( 'Select %s', 'wm-settings' ), $label );
                    echo "<p><a class='button button-large wm-select-media' title=\"{$label}\">{$select_text}</a> ";
                    $remove_text = sprintf( __( 'Remove %s', 'wm-settings' ), $label );
                    echo "<a class='button button-small wm-remove-media' title=\"{$label}\">{$remove_text}</a></p>";
                    echo "{$desc}</fieldset>";
                    break;
                // Text bloc
                case 'textarea':
                    echo "<textarea {$attrs} id=\"{$id}\" class=\"large-text\">{$value}</textarea>{$desc}";
                    break;
                // Multiple checkboxes
                case 'multi':
                    if ( ! is_array( $choices ) ) {
                        _e( 'No options defined.', 'wm-settings' );
                    } else {
                        echo "<fieldset id=\"{$id}\">";
                        foreach ( $choices as $n => $label ) {
                            $a = preg_replace( "/name\=\"(.+)\"/", "name='$1[{$n}]'", $attrs );
                            $check = checked( 1, $value[$n], false );
                            $choices[$n] = "<label><input {$a} type=\"checkbox\" value=\"1\" {$check} /> {$label}</label>";
                        }
                        echo implode( '<br />', $choices );
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
                        case 'dropdown-pages':
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
                        case 'image':
                        case 'upload':
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


    // ACTIONS

    add_action( 'activated_plugin', array( 'WM_Settings', 'plugin_priority' ) );
    add_action( 'init', array( 'WM_Settings', 'init' ) );
    add_action( 'admin_menu', array( 'WM_Settings', 'admin_menu' ) );
    add_action( 'admin_notices', array( 'WM_Settings', 'admin_notices' ) );
    add_action( 'customize_register', array( 'WM_Settings', 'customize_register' ) );
    // add_action( 'wp_before_admin_bar_render', array( 'WM_Settings', 'admin_notices' ) );
}

?>
