<?php

if ( ! class_exists( 'WM_Settings' ) ) {

    /**
     * Get setting value.
     *
     * @since 2.0.0
     *
     * @param string $id Section identifier.
     * @param string $name Optional. Field name.
     * @return array|mixed|null Returns all the values of a section, the single value of a field if specified, or null if nothing found.
     */
    function wm_get_setting( $id, $name = null )
    {
        // Cache values
        static $values = array();

        if ( ! $id = sanitize_key( $id ) ) {
            return null;
        }

        if ( ! array_key_exists( $id, $values ) ) {
            $values[$id] = get_option( "wm_settings_{$id}" );
        }
        if ( empty( $values[$id] ) ) {
            return null;
        }
        if ( $name ) {
            // Field value
            return array_key_exists( $name, $values[$id] ) ? $values[$id][$name] : null;
        }
        // Section values
        return $values[$id];
    }

    /**
     * Register a configuration page and its menu.
     *
     * @since 2.0.0
     *
     * @param string $name Page identifier.
     * @param string $title Optional. Page title.
     * @param boolean|array $menu {
     *     Optional. Menu parameters, false to disable the page.
     *
     *     @type string $parent Slug name of the parent menu.
     *                          Default 'themes.php'.
     *                          Accepts false to create a top level menu item.
     *     @type string $title Menu item label.
     *                         Default $page_title.
     *     @type string $capability Capability required for this menu to be displayed to the user.
     *                              Default 'manage_options'.
     *     @type string $icon_url Menu icon (for top level menu item).
     *                            Default 'dashicons-admin-generic'.
     *     @type integer $position Position in the menu order this menu should appear (for top level menu item).
     *                             Default bottom of menu structure.
     * }
     * @param array $sections {
     *     Optional. Sections list.
     *
     *     @type callable|array $section_id {
     *         Section declaration. Can be returned by callback.
     *
     *         @type string $title Optional. Section title.
     *         @type string $description Optional. Section description.
     *         @type array $fields {
     *             Optional. Setting declaration.
     *
     *             @type string|array $field_name {
     *                 Field declaration. Used as label if string.
     *
     *                 @type string $type Type.
     *                                    Default 'text'.
     *                                    Accepts 'checkbox', 'textarea', 'radio', 'select', 'multi', 'media', 'action', 'color' or any valid HTML5 input type attribute.
     *                 @type string $label Optional. Label.
     *                                     Accepts false to hide the label column on this field.
     *                 @type string $description Optional. Description.
     *                 @type string $default Optional. Default value.
     *                 @type callable $sanitize Optional. Function to apply in place of the default sanitation.
     *                                          Receive the input $value and the option $name as parameters, and is expected to return a properly sanitised value.
     *                 @type array $attributes Optional. Associative array( 'name' => value ) of HTML attributes.
     *                 @type array $choices Only for 'radio', 'select' and 'multi' field types. Associative array( 'key' => label ) of options.
     *                 @type callable $action Only for 'action' field type. A callback responding with either wp_send_json_success (success) or wp_send_json_error (failure),
     *                                        where the optional sent $data is either the message to display (string), or array( 'reload' => true ) if the page needs to reload on action's success.
     *             }
     *         }
     *         @type boolean $customize Optional. Whether to display this section in the "Customizer".
     *                                  Default false.
     *     }
     * }
     * @param array $config {
     *     Optional. An array of miscellaneous arguments.
     *
     *     @type boolean $tabs Whether to display the different sections as «tabs» or not.
     *                         There must be several sections, and they must have a title.
     *                         Default false.
     *     @type string $submit Text of the submit button.
     *                          Default 'Save Settings'.
     *     @type string $reset Text of the reset button.
     *                         Default 'Reset Settings'.
     *                         Accepts false to disable the button.
     *     @type string $description Page description.
     *     @type string $updated Message of the success notice.
     *                           Default 'Settings saved.'.
     *                           Accepts false to disable the notice.
     * }
     * @return WM_Settings The page instance created.
     */
    function wm_add_settings_page( $page_id = 'custom_settings', $title = null, $menu = true, array $sections = null, array $config = array() )
    {
        return new WM_Settings( $page_id, $title, $menu, $sections, $config );
    }

    /**
     * Register a customizer section.
     *
     * @since 2.0.0
     *
     * @param string $section_id Section identifier.
     * @param string $title Optional. Section title.
     * @param array $fields {
     *     Optional. Setting declaration.
     *
     *     @type string|array $field_name {
     *         Field declaration. Used as label if string.
     *
     *         @type string $type Type.
     *                            Default 'text'.
     *                            Accepts 'checkbox', 'textarea', 'radio', 'select', 'multi', 'media', 'action', 'color' or any valid HTML5 input type attribute.
     *         @type string $label Optional. Label.
     *                             Accepts false to hide the label column on this field.
     *         @type string $description Optional. Description.
     *         @type string $default Optional. Default value.
     *         @type callable $sanitize Optional. Function to apply in place of the default sanitation.
     *                                  Receive the input $value and the option $name as parameters, and is expected to return a properly sanitised value.
     *         @type array $attributes Optional. Associative array( 'name' => value ) of HTML attributes.
     *         @type array $choices Only for 'radio', 'select' and 'multi' field types. Associative array( 'key' => label ) of options.
     *         @type callable $action Only for 'action' field type. A callback responding with either wp_send_json_success (success) or wp_send_json_error (failure),
     *                                where the optional sent $data is either the message to display (string), or array( 'reload' => true ) if the page needs to reload on action's success.
     *     }
     * }
     * @param string $description Optional. Section description.
     */
    function wm_add_customize_section( $section_id = 'custom_section', $title = null, array $fields = null, $description = null )
    {
        return new WM_Settings( "customize_{$section_id}", $title, false, array(
            $section_id => array(
                'title'       => $title,
                'description' => $description,
                'fields'      => $fields,
                'customize'   => true
            )
        ) );
    }


    /**
     * Instanciate user defined settings pages.
     *
     * @since 2.0.0
     */
    class WM_Settings
    {
        private $page_id,        // Page id
            $sections = array(), // User defined settings
            $empty = true,       // Page status
            $notices;            // Page notices

        public $title,           // Page title
            $menu,               // Menu configuration
            $config;             // Page configuration

        private static $instances = array(); // Page instances


        // PAGE CONSTRUCTOR

        public function __construct( $page_id, $title = null, $menu = true, array $sections = null, array $config = array() )
        {
            $this->page_id = sanitize_key( $page_id );
            $this->title = $title
                ? (string) $title
                : (string) $page_id;

            $this->menu = ( $menu || is_array( $menu ) ) // Empty array means defaults
                ? array(
                    'parent'     => 'options-general.php', // Parent page id
                    'title'      => $this->title,          // Menu item title
                    'capability' => 'manage_options',      // User capability to access
                    'icon_url'   => null,                  // Menu item icon (for parent page only)
                    'position'   => null                   // Menu item priority
                )
                : false; // "Disable" page
            if ( is_array( $menu ) ) {
                $this->menu = array_merge( $this->menu, $menu );
            }

            $this->config  = array_merge( array(
                'description' => null,                                  // Page description
                'submit'      => __( 'Save Settings', 'wm-settings' ),  // Submit button text
                'reset'       => __( 'Reset Settings', 'wm-settings' ), // Reset button text (false to disable)
                'tabs'        => false,                                 // Use tabs to switch sections
                'updated'     => __( 'Settings saved.', 'wm-settings')  // Custom success message
            ), $config );

            // Register user defined settings
            $this->add_sections( (array) $sections );

            // Get cached notices
            $this->notices = array_filter( (array) get_transient( "wm_settings_{$this->page_id}_notices" ) );

            // Record this instance
            self::$instances[$this->page_id] = $this;

            // Admin menu hook
            add_action( 'admin_init', array( $this, 'admin_init' ), 101 );
        }


        // USER METHODS

        public function add_sections( array $sections )
        {
            foreach ( $sections as $id => $section ) {
                $this->add_section( $id, $section );
            }
        }
        public function add_section( $id, $section )
        {
            if ( $id = sanitize_key( $id ) && $section && empty( $this->sections[$id] ) ) {
                $this->sections[$id] = $section;
            }
        }

        public function get_sections()
        {
            foreach ( array_keys( $this->sections ) as $id ) {
                $this->get_section( $id );
            }
            return array_filter( $this->sections );
        }

        public function get_section( $id )
        {
            if ( $id = sanitize_key( $id ) && ! empty( $this->sections[$id] ) ) {
                if ( $this->sections[$id] instanceof WM_Settings_Section ) {
                    return $this->sections[$id];
                }

                $arguments = is_callable( $this->sections[$id] )
                    ? call_user_func( $this->sections[$id] )
                    : $this->sections[$id];
                if ( is_array( $arguments ) ) {
                    return $this->sections[$id] = new WM_Settings_Section( $id, $arguments );
                }
            }
            return $this->sections[$id] = null;
        }


        // INSTANCE METHODS

        protected function set_ajax()
        {
            foreach ( $this->get_sections() as $id => $section ) {
                foreach ( $section->get_fields() as $name => $field ) {
                    if ( 'action' === $field->type && is_callable( $field->action ) ) {
                        add_action( "wp_ajax_wm_settings_{$id}_{$name}", $field->action );
                    }
                }
            }
        }

        protected function set_menu()
        {
            if ( $this->menu ) {
                if ( $this->menu['parent'] ) {
                    // As child item
                    $hookname = add_submenu_page( $this->menu['parent'], $this->title, $this->menu['title'], $this->menu['capability'], $this->page_id, array( $this, 'render' ) );
                } else {
                    // As parent item
                    $hookname = add_menu_page( $this->title, $this->menu['title'], $this->menu['capability'], $this->page_id, array( $this, 'render' ), $this->menu['icon_url'], $this->menu['position'] );
                    if ( $this->title !== $this->menu['title'] ) {
                        // "Main" child item
                        add_submenu_page( $this->page_id, $this->title, $this->title, $this->menu['capability'], $this->page_id );
                    }
                }
                // Enable page display
                add_action( "load-{$hookname}", array( $this, 'load' ) );
            }
        }

        protected function set_customize( &$wp_customize )
        {
            foreach ( $this->get_sections() as $id => $section ) {
                if ( $section->customize ) {

                    $wp_customize->add_section( $id, (array) $section ); // TO MAKE BETTER

                    foreach ( $section->get_fields() as $name => $field ) {

                    	$wp_customize->add_setting( "wm_settings_{$id}[{$name}]", array(
            				'default'              => $field['default'],
                            'type'                 => 'option',
                            'sanitize_callback'    => array( $field, 'sanitize_value' ),
                            'capability'           => $this->menu['capability']
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
                                $this->add_notice( sprintf( __( 'Sorry but "<strong>%s</strong>" is not a valid <em>Customize Control</em> type quite yet.', 'wm-settings' ), $field['type'] ), 'warning' );
                                continue;
                            case 'media':
                                $this->add_notice( __( 'Sorry but "<strong>media</strong>" is not a valid <em>Customize Control</em> type quite yet. Use "<strong>upload</strong>" or "<strong>image</strong>" instead.' ), 'warning' );
                                continue;
                            default:
                                $control = new WP_Customize_Control( $wp_customize, "{$id}_{$name}", $args );
                        }

                        $wp_customize->add_control( $control );
                    }
                }
            }
        }


        // WORDPRESS ACTIONS

        // Register ajax actions
        public static function init()
        {
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                do_action( 'wm_register_settings_pages' );
                // Register page instances ajax actions
                foreach ( self::$instances as $page ) {
                    $page->set_ajax();
                }
            } else {
                add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
                add_action( 'customize_register', array( __CLASS__, 'customize_register' ) );
            }
        }

        // Register menu items
        public static function admin_menu()
        {
            // Public hook to register pages
            do_action( 'wm_register_settings_pages' );
            // Register page instances menu item
            foreach ( self::$instances as $page ) {
                $page->set_menu();
            }
        }

        // Register customize actions
        public static function customize_register( &$wp_customize )
        {
            // Public hook to register pages
            do_action( 'wm_register_settings_pages' );
            // Register pag instances customize sections
            foreach ( self::$instances as $page ) {
                $page->set_customize( $wp_customize );
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
            foreach ( $this->get_sections() as $id => $section ) {

                $setting_id = "wm_settings_{$id}";

                if ( ! get_option( $setting_id ) ) {
                    // Initialise option with default values
                    add_option( $setting_id, $section->sanitize_setting( false ) );
                } else if ( $reset && isset( $_POST[$setting_id] ) ) {
                    // Force default values
                    $_POST[$setting_id]['wm_settings_reset'] = true;
                }

                // Register setting
                register_setting( $this->page_id, $setting_id, array( $section, 'sanitize_setting' ) );
                // Register section
                add_settings_section( $setting_id, $section->title, array( $section, 'render' ), $this->page_id );

                if ( $fields = $section->get_fields() ) {
                    $this->empty = false;

                    foreach ( $fields as $name => $field ) {
                        $args = array_merge( (array) $field, array(
                            'id'        => "{$setting_id}_{$name}",
                            'name'      => "{$setting_id}[{$name}]",
                            'value'     => get_setting( $id, $name ),
                            'label_for' => false === $field['label']
                                ? 'hidden'
                                : $name
                        ) );
                        add_settings_field( $args['id'], $args['label'], array( $field, 'render' ), $this->page_id, $setting_id, $args );
                    }
                }
            }
        }

        // Load page
        public function load()
        {
            // Update callback
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
                do_action( "{$this->page_id}_settings_updated" );
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
                'url'     => admin_url( 'admin-ajax.php' ),
                'spinner' => admin_url( 'images/spinner.gif' )
            ) );
            // Styles
            wp_enqueue_style( 'wm-settings', plugins_url( 'wm-settings.css' , __FILE__ ) );
            wp_enqueue_style( 'wp-color-picker' );
        }


        // SETTINGS API CALLBACKS

        // Page display callback
        public function render()
        { ?>
            <form action="options.php" method="POST" enctype="multipart/form-data" class="wrap wm-settings-form">
                <h2><?php echo $this->title; ?></h2>
                <?php
                    // Prepare notices
                    $this->set_notices();
                    // Display notices
                    settings_errors( $this->page_id );
                    if ( $this->config['description'] ) {
                        echo wpautop( $this->config['description'] );
                    }
                ?>
                <div class="wm-settings-sections"><?php
                    // Tabs
                    if ( ! $this->empty && $this->config['tabs'] && count( $this->get_sections() ) > 1 ) {
                        echo "<div class=\"wm-settings-tabs\"></div>";
                    }
                    // Display sections
                    do_settings_sections( $this->page_id );
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
                    settings_fields( $this->page_id );
                ?>
            </form>
        <?php }
    }


    /**
     * Instanciate settings pages sections.
     *
     * @since 2.0.2
     */
    class WM_Settings_Section
    {
        private $id,
            $fields = array();

        public $title,
            $description,
            $customize = false;


        // SECTION CONSTRUCTOR

        public function __construct( $id, array $section )
        {
            $this->id = $id;

            $section = array_merge( array(
                'title'       => null,
                'fields'      => array(),
                'description' => null,
                'customize'   => false
            ), $section );

            $this->title = $section['title'];
            $this->description = $section['description'];
            $this->customize = $section['customize'];

            // Register user defined settings
            $this->add_fields( (array) $section['fields'] );
        }


        // USER METHODS

        // Register settings callbacks
        public function add_fields( array $fields )
        {
            foreach ( $fields as $name => $field ) {
                $this->add_field( $name, $field );
            }
        }
        public function add_field( $name, $field )
        {
            if ( $name = sanitize_key( $name ) && $field && empty( $this->fields[$name] ) ) {
                $this->fields[$name] = $field;
            }
        }

        // Prepare fields
        public function get_fields()
        {
            foreach ( array_keys( $this->fields ) as $name ) {
                $this->get_field( $name );
            }
            return array_filter( $this->fields );
        }

        public function get_field( $name )
        {
            if ( $name = sanitize_key( $name ) && ! empty( $this->fields[$name] ) ) {
                if ( $this->fields[$name] instanceof WM_Settings_Field ) {
                    return $this->fields[$name];
                }

                return $this->fields[$name] = new WM_Settings_Field( $name, is_callable( $this->fields[$name] )
                    ? call_user_func( $this->fields[$name] )
                    : $this->fields[$name]
                );
            }
            return $this->fields[$name] = null;
        }

        // Sanitize values before save
        public function sanitize_setting( $inputs = false )
        {
            // Array of sanitized values
            $values = array();

            // Section's fields
            foreach ( $this->get_fields() as $name => $field ) {

                if ( false === $inputs || ! empty( $inputs['wm_settings_reset'] ) ) {
                    // Set default as input
                    $input = $field->default;
                } else {
                    // Set posted input
                    $input = isset( $inputs[$name] )
                        ? $inputs[$name]
                        : null;
                }

                $values[$name] = $field->sanitize_value( $input );
            }
            return $values;
        }

        // Section display callback
        public function render()
        {
            if ( $this->description ) {
                echo wpautop( $this->description );
            }
        }
    }

    /**
     * Instanciate settings pages sections.
     *
     * @since 2.0.0
     */
    class WM_Settings_Field
    {
        private $name;

        public $type,
            $label,
            $description,
            $default,
            $sanitize,
            $attributes,
            $choices,
            $action;

        public function __construct( $name, $field )
        {
            $this->name = $name;

            // Set field
            $field = array_merge( array(
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
            ), array_filter( (array) $field ) );

            $this->type = $field['type'];
            $this->label = $field['label'];
            $this->description = $field['description'];
            $this->default = $field['default'];
            $this->sanitize = $field['sanitize'];
            $this->attributes = $field['attributes'];
            $this->choices = $field['choices'];
            $this->action = $field['action'];
        }

        public function sanitize_value( $input )
        {
            // "Custom" sanitation
            if ( $this->sanitize ) {
                return call_user_func( $this->sanitize, $input, $this->name );
            }
            // "Default" sanitation
            switch ( $this->type )
            {
                case 'checkbox':
                    // Boolean
                    return $input
                        ? 1
                        : 0;

                case 'radio':
                case 'select':
                case 'dropdown-pages':
                    // Field option key
                    return sanitize_key( $input );

                case 'media':
                    // Attachment id
                    return absint( $input );

                case 'color':
                    // Hex color
                    return preg_match( '/^#[a-f0-9]{6}$/', $input )
                        ? $input
                        : "#808080";

                case 'textarea':
                    $value = "";
                    // Convert and keep new lines and tabulations
                    $nl = "WM-SETTINGS-NEW-LINE";
                    $tb = "WM-SETTINGS-TABULATION";
                    $lines = explode( $nl, sanitize_text_field( str_replace( "\t", $tb, str_replace( "\n", $nl, $input ) ) ) );
                    foreach ( $lines as $line ) {
                        $value .= str_replace( $tb, "\t", trim( $line ) ) . "\n";
                    }
                    return trim( $value );

                case 'multi':
                    $value = array();
                    foreach ( array_keys( $this->choices ) as $key ) {
                        $value[$key] = empty( $input[$key] )
                            ? 0
                            : 1;
                    }
                    return $value;

                case 'action':
                    return null;

                case 'email':
                    return sanitize_email( $input );

                case 'url':
                case 'image':
                case 'upload':
                    return esc_url_raw( $input );

                case 'number':
                    return floatval( $input );

                default:
                    return sanitize_text_field( $input );
            }
        }

        // Control display callback
        public static function render( $args )
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
                        if ( $value && 'media' === $type ) {
                            $src = wp_get_attachment_image_src( $value, 'full', true );
                            $v = $src[0];
                        }
                        echo "<p><img class=\"wm-preview-media\" src=\"{$v}\"></p>";
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
    }


    // ACTIONS

    add_action( 'init', array( 'WM_Settings', 'init' ) );
}

?>
