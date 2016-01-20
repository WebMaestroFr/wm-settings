<?php

/**
 * Instanciate user defined settings pages.
 *
 * @since 2.0.0
 */
class WM_Settings_Customize
{
    public $sections = array(); // User defined settings


    // PAGE CONSTRUCTOR

    public function register( $wp_customize )
    {
        add_action( 'customize_controls_enqueue_scripts', array( 'WM_Settings', 'admin_enqueue_scripts' ) );

        foreach ( $this->sections as $section ) {

            $wp_customize->add_section( $section->section_id, array(
                // 'panel'    => $this->id,
                'title'       => $section->title,
                'description' => implode( '', array_unique( $section->notices ) ) . $section->config['description']
            ) );

            foreach ( $section->fields as $field_id => $field ) {

                $wp_customize->add_setting( $field->name, array(
                    'default'           => $field->config['default'],
                    'transport'         => 'refresh',
                    'type'              => 'option',
                    'sanitize_callback' => array( $field, 'sanitize_value' )
                ) );

                $wp_customize->add_control( new WM_Settings_Customize_Control( $wp_customize, $field->id, array(
                    'label'             => $field->label,
                    'type'              => "wm_settings-{$field->type}",
                    'settings'          => $field->name,
                    'section'           => $section->section_id,
                    'wm_settings_field' => $field
                ) ) );
            }
        }
    }


    // USER METHODS

    public function __call( $name, $arguments )
    {
        if ( ! isset( $this->sections['settings'] ) ) {
            $this->add_section( 'settings', __( 'Settings', 'wm-settings' ) );
        }
        $method = array( $this->sections['settings'], $name );
        if ( is_callable( $method ) ) {
            call_user_func_array( $method, $arguments );
        }
    }

    public function add_section( $section_id, $title = null, array $config = null )
    {
        $section_key = sanitize_key( $section_id );
        return $this->sections[$section_key] = new WM_Settings_Section( $section_id, $title, $config );
    }
    public function add_sections( array $sections )
    {
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
}

// if ( class_exists( 'WP_Customize_Control' ) ) {

    class WM_Settings_Customize_Control extends WP_Customize_Control
    {
        protected $wm_settings_field;

    	public function render_content()
        {
            if ( ! empty( $this->wm_settings_field->label ) ) {
                echo "<label for=\"{$this->wm_settings_field->id}\" class=\"customize-control-title\">{$this->wm_settings_field->label}</label>";
            }
            $name = preg_quote( $this->wm_settings_field->name );
            $link = $this->get_link();
            ob_start();
            $this->wm_settings_field->render();
            echo preg_replace( "/ (name=\"{$name}\")/", " $1 {$link}", ob_get_clean() );
        }
    }

// }
