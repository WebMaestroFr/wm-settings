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

    public function __construct()
    {
        add_action( 'customize_register',  array( $this, 'customize_register' ), 102 );
    }

    public function customize_register( $wp_customize )
    {
        do_action( "wm_settings_register_customize", $this );

        foreach ( $this->sections as $section ) {

            $wp_customize->add_section( $section->section_id, array_merge( array(
                'title'       => $section->title,
                'description' => $section->config['description']
            ), is_array( $section->config['customize'] )
                ? $section->config['customize']
                : array()
            ) );

            foreach ( $section->fields as $field_id => $field ) {

                $wp_customize->add_setting( $field->name, array(
                    'default'           => $field->config['default'],
                    'type'              => 'option',
                    'sanitize_callback' => array( $field, 'sanitize_value' )
                ) );

                $args = array(
                    'label'    => $field->label,
                    'type'     => $field->type,
                    'choices'  => $field->config['choices'],
                    'settings' => $field->name,
                    'section'  => $section->section_id
                );

                switch ( $field->type ) {
                    case 'color':
                        $control = new WP_Customize_Color_Control( $wp_customize, $field->name, $args );
                        break;
                    case 'upload':
                        $control = new WP_Customize_Upload_Control( $wp_customize, $field->name, $args );
                        break;
                    case 'image':
                        $control = new WP_Customize_Image_Control( $wp_customize, $field->name, $args );
                        break;
                    case 'multi':
                    case 'action':
                        $this->add_notice( sprintf( __( 'Sorry but "<strong>%s</strong>" is not a valid <em>Customize Control</em> type quite yet.', 'wm-settings' ), $field->type ), 'warning' );
                        continue;
                    case 'media':
                        $this->add_notice( __( 'Sorry but "<strong>media</strong>" is not a valid <em>Customize Control</em> type quite yet. Use "<strong>upload</strong>" or "<strong>image</strong>" instead.' ), 'warning' );
                        continue;
                    default:
                        $control = new WP_Customize_Control( $wp_customize, $field->name, $args );
                }

                $wp_customize->add_control( $control );
            }
        }
    }

    // USER METHODS

    public function add_section( $section_id, $title = null, array $config = null, $fields = array() )
    {
        $section_key = sanitize_key( $section_id );
        return $this->sections[$section_key] = new WM_Settings_Section( $section_id, $title, $config, $fields );
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
    public function register_sections( $sections_func )
    {
        if ( is_callable( $sections_func ) ) {
            add_action( "wm_settings_{$this->page_id}_register_sections", $sections_func );
        }
    }
    public function get_section( $section_id )
    {
        $section_key = sanitize_key( $section_id );
        return empty( $this->sections[$section_key] ) ? null : $this->sections[$section_key];
    }
}
