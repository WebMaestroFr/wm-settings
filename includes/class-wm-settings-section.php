<?php

/**
 * Instanciate settings pages sections.
 *
 * @since 2.0.2
 */
class WM_Settings_Section
{
    public $section_id,
        $title,
        $config,
        $fields = array(),
        $setting_id;


    // SECTION CONSTRUCTOR

    public function __construct( $section_id, $title = null, array $config = null, array $fields = array() )
    {
        $this->section_id = sanitize_key( $section_id );
        $this->title = is_string( $title )
            ? (string) $title
            : (string) $section_id;

        $this->config  = array(
            'description' => null,  // Page description
            'submit'      => false, // Submit button text
            'reset'       => false, // Reset button text (false to disable)
            'customize'   => false  //
        );
        if ( $config ) {
            $this->config = array_merge( $this->config, $config );
            if ( true === $this->config['submit'] ) {
                $this->config['submit'] = __( 'Save Setting', 'wm-settings' );
            }
            if ( true === $this->config['reset'] ) {
                $this->config['reset'] = __( 'Reset Setting', 'wm-settings' );
            }
            if ( $this->config['customize'] ) {
                $this->config['customize'] = array_merge( ( is_array( $this->config['customize'] )
                    ? $this->config['customize']
                    : array()
                ), array(
                    'title'       => $this->title,
                    'description' => $this->config['description']
                ) );
            }
        }

        $this->setting_id = "wm_settings_{$section_id}";

        if ( ! get_option( $this->setting_id ) ) {
            // Initialise option with default values
            add_option( $this->setting_id, $this->sanitize_setting( false ) );
        }

        // Register user defined settings
        foreach ( $fields as $field_id => $field ) {
            if ( is_string( $field ) ) {
                $field = array( $field );
            }
            array_unshift( $field, $field_id );
            call_user_func_array( array( $this, 'add_field' ), $field );
        }

    	add_action( 'customize_register',  array( $this, 'customize_register' ), 104 );
    }


    // USER METHODS

    // Register settings callbacks
    public function add_field( $field_id, $label = null, $type = 'text', array $config = array() )
    {
        if ( ( $field_id = sanitize_key( $field_id ) ) && empty( $this->fields[$field_id] ) ) {
            return $this->fields[$field_id] = new WM_Settings_Field( $this, $field_id, $label, $type, $config );
        }
        return $this->fields[$field_id];
    }

    public function get_field( $field_id )
    {
        if ( ( $field_id = sanitize_key( $field_id ) ) && ! empty( $this->fields[$field_id] ) ) {
            return $this->fields[$field_id];
        }
        return null;
    }

    public function add_notice( $message, $type = 'info', $code = 'wm-settings' )
    {
        // add_settings_error( $this->section_id, $code, $message, $type );
    }

    public function customize_register( $wp_customize )
    {
        if ( $this->config['customize'] ) {

            $wp_customize->add_section( $this->section_id, $this->config['customize'] );

            foreach ( $this->fields as $field_id => $field ) {

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
                    'section'  => $this->section_id
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
        return $wp_customize;
    }

    // Sanitize values before save
    public function sanitize_setting( $inputs = false )
    {
        // Array of sanitized values
        $values = array();

        // Section's fields
        foreach ( $this->fields as $name => $field ) {

            if ( false === $inputs || ( $this->config['reset'] && isset( $inputs['wm_settings_section_reset'] ) ) ) {
                // Set default as input
                $input = $field->config['default'];
            } else {
                // Set posted input
                $input = isset( $inputs[$name] ) ? $inputs[$name] : null;
            }

            $values[$name] = $field->sanitize_value( $input );
        }
        return $values;
    }

    // Section display callback
    public function render()
    {
        ?><input type="hidden" class="wm-settings-section"><?php

        settings_errors( $this->section_id );

        if ( $this->config['description'] ) {
            echo wpautop( $this->config['description'] );
        }

        if ( ( $this->config['submit'] || $this->config['reset'] ) && count( $this->fields ) > 1 ) { ?>
            <p class="submit"><?php

                // Submit button
                if ( $this->config['submit'] ) {
                    submit_button( $this->config['submit'], 'large primary', 'wm_settings_section_submit', false );
                }

                // Reset button
                if ( $this->config['reset'] ) {
                    $confirm = esc_js( __( 'Do you really want to reset these settings to their default values ?', 'wm-settings' ) );
                    submit_button( $this->config['reset'], 'small', "wm_settings_{$this->section_id}[wm_settings_section_reset]", false, array(
                        'onclick' => "return confirm('{$confirm}');"
                    ) );
                }

            ?></p>
        <?php }
    }
}
