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

        // $wp_customize->add_panel( $this->id, array(
        //     'title'       => $this->title,
        //     'description' => $this->description
        // ) );

        foreach ( $this->sections as $section ) {

            $wp_customize->add_section( $section->section_id, array(
                // 'panel'    => $this->id,
                'title'       => $section->title,
                'description' => $section->config['description']
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
}

// if ( class_exists( 'WP_Customize_Control' ) ) {

    class WM_Settings_Customize_Control extends WP_Customize_Control
    {
        protected $wm_settings_field;

    	public function render_content()
        {
            echo "<label>";
            if ( ! empty( $this->label ) ) {
                echo "<span class=\"customize-control-title\">{$this->label}</span>";
            }
            $this->wm_settings_field->render();
            echo "</label>";
        }
    }

// }
