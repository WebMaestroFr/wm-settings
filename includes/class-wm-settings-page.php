<?php

/**
 * Instanciate user defined settings pages.
 *
 * @since 2.0.0
 */
class WM_Settings_Page
{
    private $empty = true;       // Page status

    public $page_id,         // Page id
        $title,              // Page title
        $menu,               // Menu configuration
        $config,             // Page configuration
        $sections = array(); // User defined settings


    // PAGE CONSTRUCTOR

    public function __construct( $page_id, $title = null, $menu = true, array $config = null, $sections = null )
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
            'reset'       => __( 'Reset Settings', 'wm-settings' ), // Reset button text (false to disable)
            'tabs'        => false,                                 // Use tabs to switch sections
            'updated'     => __( 'Settings saved.', 'wm-settings')  // Custom success message
        );
        if ( $config ) {
            $this->config = array_merge( $this->config, $config );
        }

        // Register user defined settings
        if ( is_callable( $sections ) ) {
            add_action( "wm_settings_register_{$this->page_id}_sections", $sections );
        } else if ( is_array( $sections ) ) {
            foreach ( $sections as $section_id => $section ) {
                array_unshift( $section, $section_id );
                call_user_func_array( array( $this, 'add_section' ), $section );
            }
        }

        add_action( 'admin_menu',          array( $this, 'admin_menu' ),         102 );
        add_action( 'admin_init',          array( $this, 'admin_init' ),         102 );
    	add_action( 'customize_register',  array( $this, 'register_sections' ),  102 );
        add_action( 'wp_ajax_wm_settings', array( $this, 'ajax_action' ),        102 );
    }


    // USER METHODS

    public function add_section( $section_id, $title = null, array $config = null, array $fields = array() )
    {
        if ( ( $section_id = sanitize_key( $section_id ) ) && empty( $this->sections[$section_id] ) ) {
            return $this->sections[$section_id] = new WM_Settings_Section( $section_id, $title, $config, $fields );
        }
    }

    public function get_section( $section_id )
    {
        if ( ( $section_id = sanitize_key( $section_id ) ) && ! empty( $this->sections[$section_id] ) ) {
            return $this->sections[$section_id];
        }
        return null;
    }

    public function add_notice( $message, $type = 'info', $code = 'wm-settings' )
    {
        add_settings_error( $this->page_id, $code, $message, $type );
    }


    // PRIVATE ACTIONS

    // Register menu items
    public function register_sections()
    {
        // Public hook to register pages
        do_action( "wm_settings_register_{$this->page_id}_sections", $this );
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
        $this->register_sections();

        // Reset request
        if ( $reset = ( $this->config['reset'] && isset( $_POST["wm_settings_page_{$this->page_id}_reset"] ) ) ) {
            // Prepare notice
            $this->add_notice( __( 'All settings have been reset to their default values.', 'wm-settings' ) );
        }

        // Prepare sections
        foreach ( $this->sections as $section_id => $section ) {

            if ( $reset ) {
                // Force default values
                $_POST[$section->setting_id] = false;
            }

            // Register setting
            register_setting( $this->page_id, $section->setting_id, array( $section, 'sanitize_setting' ) );
            // Register section
            add_settings_section( $section->setting_id, $section->title, array( $section, 'render' ), $this->page_id );

            if ( $section->fields ) {
                $this->empty = false;

                foreach ( $section->fields as $field_id => $field ) {
                    add_settings_field( $field->name, $field->label, array( $field, 'render' ), $this->page_id, $section->setting_id, array(
                        'label_for' => ( false === $field->label ) ? 'hidden' : $field_id
                    ) );
                }
            }
        }
    }

    public function ajax_action()
    {
        $this->register_sections();

        if ( ( $this->page_id === $_POST['page_id'] )
            && preg_match( '/^wm_settings_(.+)\[(.+)\]$/', $_POST['name'], $matches )
            && $section = $this->get_section( $matches[1] )
            && $field = $section->get_field( $matches[2] )
            && is_callable( $field->action )
        ) {
            // Call action
            call_user_func( $field->action );
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
        add_action( 'admin_enqueue_scripts', array( 'WM_Settings', 'admin_enqueue_scripts' ) );
    }


    // SETTINGS API CALLBACKS

    // Page display callback
    public function render()
    { ?>
        <form action="options.php" class="wrap wm-settings-page" id="wm-settings-page-<?php echo $this->page_id; ?>" method="POST" enctype="multipart/form-data">
            <h2><?php echo $this->title; ?></h2>
            <?php
                if ( $this->config['description'] ) {
                    echo wpautop( $this->config['description'] );
                }
                // Display notices
                settings_fields( $this->page_id );
                settings_errors( $this->page_id );
            ?>
            <div class="wm-settings-sections"><?php
                // Tabs
                if ( $this->config['tabs'] && count( $this->sections ) > 1 ) {
                    echo "<div class=\"wm-settings-tabs\"></div>";
                }
                // Display sections
                do_settings_sections( $this->page_id );
            ?></div>
            <?php if ( ! $this->empty ) { ?>
                <p class="submit"><?php
                    // Submit button
                    submit_button( $this->config['submit'], 'large primary', "wm_settings_page_{$this->page_id}_submit", false );
                    // Reset button
                    if ( $this->config['reset'] ) {
                        $confirm = esc_js( __( 'Do you really want to reset these settings to their default values ?', 'wm-settings' ) );
                        submit_button( $this->config['reset'], 'small', "wm_settings_page_{$this->page_id}_reset", false, array(
                            'onclick' => "return confirm('{$confirm}');"
                        ) );
                    }
                ?></p>
            <?php } ?>
        </form>
    <?php }
}
