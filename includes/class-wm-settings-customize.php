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
                'title'       => $section->title,
                'description' => $section->config['description'],
                // 'panel'       => $this->id
            ) );

            foreach ( $section->fields as $field_id => $field ) {

                $wp_customize->add_setting( $field->name, array(
                    'default'           => $field->config['default'],
                    'type'              => 'option',
                    'sanitize_callback' => array( $field, 'sanitize_value' )
                ) );

                $wp_customize->add_control( new WM_Settings_Customize_Control( $wp_customize, $field->name, array_merge( $field->config, array(
                    'label'           => $field->label,
                    'type'            => "wm_{$field->type}",
                    'settings'        => $field->name,
                    'section'         => $section->section_id,
                    'render_callback' => array( $field, 'render' )
                ) ) ) );
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


class WM_Settings_Customize_Control extends WP_Customize_Control
{
    protected $render_callback;

	public function render_content()
    {
        if ( is_callable( $this->render_callback ) ) { ?>
            <label>
                <?php if ( ! empty( $this->label ) ) { ?>
                    <span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
                <?php }
                call_user_func( $this->render_callback ); ?>
            </label>
        <?php }
    }
}
