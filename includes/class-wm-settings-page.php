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
        $sections = array(), // User defined settings
        $main;


    // PAGE CONSTRUCTOR

    public function __construct( $page_id, $title = null, $menu = true, array $config = null, $fields_func = null )
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
            'description' => null,                                  // Page description
            'submit'      => __( 'Save Settings', 'wm-settings' ),  // Submit button text
            'reset'       => false, // Reset button text (false to disable)
            'tabs'        => false,                                 // Use tabs to switch sections
            'updated'     => __( 'Settings saved.', 'wm-settings')  // Custom success message
        );
        if ( $config ) {
            $this->config = array_merge( $this->config, $config );
            if ( true === $this->config['reset'] ) {
                $this->config['reset'] = __( 'Reset Settings', 'wm-settings' );
            }
            if ( true === $this->config['tabs'] ) {
                $this->config['tabs'] = $this->menu ? $this->menu['title'] : $this->title;
            }
        }

        // Default section
        $this->main = $this->add_section( $this->page_id, $this->config['tabs'], array(
            'description' => $this->config['description']
        ) );

        $this->register_fields( $fields_func );

        add_action( 'admin_menu', array( $this, 'admin_menu' ), 102 );
        add_action( 'admin_init', array( $this, 'admin_init' ), 102 );
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
    public function add_sections( array $sections ) {
        foreach ( $sections as $section_id => $section ) {
            if ( is_string( $section ) ) {
                $section = array( $section );
            }
            array_unshift( $section, $section_id );
            call_user_func_array( array( $this, 'add_section' ), $section );
        }
    }
    public function get_section( $section_id )
    {
        $section_key = sanitize_key( $section_id );
        return empty( $this->sections[$section_key] ) ? null : $this->sections[$section_key];
    }

    public function register_fields( $sections_func )
    {
        if ( is_callable( $sections_func ) ) {
            add_action( "wm_settings_{$this->page_id}_register_fields", $sections_func, 102 );
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
            add_action( "load-{$hookname}", array( $this, 'load' ), 102 );
        }
    }

    // Register settings
    public function admin_init()
    {
        // Public hook to register pages
        do_action( "wm_settings_{$this->page_id}_register_fields", $this );

        // Reset request
        if ( $reset = ( $this->config['reset'] && isset( $_POST["wm-settings-{$this->page_id}-reset"] ) ) ) {
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
        // Update callback
        if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
            do_action( "wm_settings_{$this->page_id}_updated" );
        }

        // Enqueue page scripts
        add_action( 'admin_enqueue_scripts', array( 'WM_Settings', 'admin_enqueue_scripts' ), 102 );

        foreach ( $this->sections as $section_id => $section ) {

            // Register section
            add_settings_section( $section->setting_id, $section->title, array( $section, 'render' ), $this->page_id );

            foreach ( $section->fields as $field_id => $field ) {
                add_settings_field( $field->name, $field->label, array( $field, 'render' ), $this->page_id, $section->setting_id, array(
                    'label_for' => $field_id
                ) );
            }
        }
    }


    // SETTINGS API CALLBACKS

    // Page display callback
    public function render()
    { ?>
        <div class="wrap wm-settings-page" id="wm-settings-page-<?php echo $this->page_id; ?>">
            <h1><?php echo $this->title; ?></h1>
            <form action="options.php" method="POST" enctype="multipart/form-data"><?php
                settings_fields( $this->page_id );
                if ( $this->config['tabs'] ) { ?>
                    <h2 class="nav-tab-wrapper wm-settings-tabs"></h2>
                <?php } ?>
                <div class="wm-settings-sections"><?php
                    // Display sections
                    do_settings_sections( $this->page_id );
                ?></div>
                <p class="submit"><?php
                    // Submit button
                    submit_button( $this->config['submit'], 'large primary wm-settings-submit', "wm-settings-{$this->page_id}-submit", false );
                    // Reset button
                    if ( $this->config['reset'] ) {
                        $confirm = esc_js( __( 'Do you really want to reset these settings to their default values ?', 'wm-settings' ) );
                        submit_button( $this->config['reset'], 'small wm-settings-reset', "wm-settings-{$this->page_id}-reset", false, array(
                            'onclick' => "return confirm('{$confirm}');"
                        ) );
                    }
                ?></p>
            </form>
        </div>
    <?php }
}
