<?php

/**
 * Instanciate user defined settings pages.
 *
 * @since 2.0.0
 */
class WM_Settings_Page
{
    public $page_id,         // Page id
        $title,              // Page title
        $menu,               // Menu configuration
        $config,             // Page configuration
        $sections = array(); // User defined settings


    // PAGE CONSTRUCTOR

    public function __construct( $page_id, $title = null, $menu = true, array $config = null, $register_func = null )
    {
        $this->page_id = sanitize_key( $page_id );
        $this->title = is_string( $title )
            ? (string) $title
            : (string) $page_id;

        if ( false === $menu ) {
            $this->menu = false;
        } else {
            $this->menu = array(
                'parent'     => 'options-general.php',     // Parent page id
                'title'      => $this->title,              // Menu item title
                'capability' => 'manage_options',          // User capability to access
                'icon_url'   => 'dashicons-admin-generic', // Menu item icon (for parent page only)
                'position'   => null                       // Menu item priority
            );
            if ( is_array( $menu ) ) {
                $this->menu = array_merge( $this->menu, $menu );
            } else if ( true === $menu ) {
                $this->menu['parent'] = false;
            } else if ( is_string( $menu ) ) {
                $this->menu['parent'] = $menu;
            }
        }

        $this->config  = array(
            'description' => null,                                 // Page description
            'submit'      => __( 'Save Settings', 'wm-settings' ), // Submit button text
            'reset'       => false,                                // Reset button text (false to disable)
            'tabs'        => false,                                // Use tabs to switch sections
            'updated'     => __( 'Settings saved.', 'wm-settings') // Custom success message
        );
        if ( $config ) {
            $this->config = array_merge( $this->config, $config );
            if ( true === $this->config['reset'] ) {
                $this->config['reset'] = __( 'Reset Settings', 'wm-settings' );
            }
        }

        // Default section
        $this->add_section( $this->page_id, null, array(
            'description' => $this->config['description']
        ) );

        $this->register( $register_func );

        add_action( 'admin_menu', array( $this, 'admin_menu' ), 102 );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
    }


    // USER METHODS

    public function __call( $name, $arguments )
    {
        $method = array( $this->sections[$this->page_id], $name );
        if ( is_callable( $method ) ) {
            call_user_func_array( $method, $arguments );
        }
    }

    public function add_section( $section_id, $title = null, array $config = null )
    {
        $section_key = sanitize_key( $section_id );
        return $this->sections[$section_key] = new WM_Settings_Section( $section_id, $title, $config );
    }
    public function get_section( $section_id )
    {
        $section_key = sanitize_key( $section_id );
        return empty( $this->sections[$section_key] ) ? null : $this->sections[$section_key];
    }

    public function register( $register_func )
    {
        if ( is_callable( $register_func ) ) {
            add_action( "wm_settings_{$this->page_id}_register", $register_func );
        }
    }


    // WORDPRESS METHODS

    public function admin_menu()
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

    // Register settings
    public function admin_init()
    {
        // Public hook to register pages
        do_action( "wm_settings_{$this->page_id}_register", $this );

        // Reset request
        if ( $this->config['reset'] && isset( $_POST["wm_settings_{$this->page_id}_reset"] ) ) {
            foreach ( $this->sections as $section ) {
                $_POST[$section->setting_id] = $section->sanitize_setting( false );
            }
            // Prepare notice
            $this->add_notice( __( 'All settings have been reset to their default values.', 'wm-settings' ) );
        }

        foreach ( $this->sections as $section_id => $section ) {
            // Register setting
            register_setting( $this->page_id, $section->setting_id, array( $section, 'sanitize_setting' ) );
        }
    }

    // Load page
    public function load()
    {
        // Enqueue page scripts
        add_action( 'admin_enqueue_scripts', array( 'WM_Settings', 'admin_enqueue_scripts' ) );

        if ( ! empty( $_REQUEST['settings-updated'] ) && $this->config['updated'] ) {
            $this->add_notice( $this->config['updated'], 'success' );
        }

        foreach ( $this->sections as $section_id => $section ) {

            // Register section
            add_settings_section( $section->setting_id, $section->title, array( $section, 'render' ), $this->page_id );

            foreach ( $section->fields as $field_id => $field ) {
                add_settings_field( $field->name, $field->label, array( $field, 'render' ), $this->page_id, $section->setting_id, array(
                    'label_for' => $field->id
                ) );
            }
        }
    }


    // SETTINGS API CALLBACKS

    // Page display callback
    public function render()
    { ?>
        <div id="wm-settings-page-<?php echo $this->page_id; ?>" class="wrap wm-settings-page<?php
            if ( $this->config['tabs'] ) { echo " tabs"; }
            // if ( $this->config['ajax'] ) { echo " ajax"; }
        ?>">
            <h1><?php echo $this->title; ?></h1>
            <form action="options.php" method="POST" enctype="multipart/form-data">
                <?php settings_fields( $this->page_id ); ?>
                <div class="wm-settings-sections">
                    <?php do_settings_sections( $this->page_id ); ?>
                </div>
                <p class="submit">
                    <?php
                        // Submit button
                        submit_button( $this->config['submit'], 'large primary wm-settings-submit', "wm_settings_{$this->page_id}_submit", false );
                        // Reset button
                        if ( $this->config['reset'] ) {
                            $confirm = esc_js( __( 'You are about to reset all these settings to their default values.', 'wm-settings' ) );
                            submit_button( $this->config['reset'], 'small wm-settings-reset', "wm_settings_{$this->page_id}_reset", false, array(
                                'onclick' => "return confirm('{$confirm}');"
                            ) );
                        }
                    ?>
                </p>
            </form>
        </div>
    <?php }
}
