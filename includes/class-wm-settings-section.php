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

    public function __construct( $section_id, $title = null, array $config = null, $fields = array() )
    {
        $this->section_id = sanitize_key( $section_id );
        $this->title = is_string( $title )
            ? (string) $title
            : (string) $section_id;

        $this->config  = array(
            'description' => null,   // Page description
            'submit'      => false,  // Submit button text
            'reset'       => false,  // Reset button text (false to disable)
            'customize'   => array() //
        );
        if ( $config ) {
            $this->config = array_merge( $this->config, $config );
            if ( true === $this->config['submit'] ) {
                $this->config['submit'] = __( 'Save Setting', 'wm-settings' );
            }
            if ( true === $this->config['reset'] ) {
                $this->config['reset'] = __( 'Reset Setting', 'wm-settings' );
            }
        }

        $this->setting_id = "wm_settings_{$section_id}";

        if ( ! get_option( $this->setting_id ) ) {
            // Initialise option with default values
            add_option( $this->setting_id, $this->sanitize_setting( false ) );
        }

        // Register user defined settings
        $this->add_fields( $fields );
    }


    // USER METHODS

    // Register settings callbacks
    public function add_field( $field_id, $label = null, $type = 'text', array $config = array() )
    {
        $field_key = sanitize_key( $field_id );
        return $this->fields[$field_key] = new WM_Settings_Field( $this, $field_id, $label, $type, $config );
    }

    public function add_fields( $fields )
    {
        if ( is_callable( $fields ) ) {
            add_action( "wm_settings_register_{$this->section_id}_fields", $fields );
        } else if ( is_array( $fields ) ) {
            foreach ( $fields as $field_id => $field ) {
                if ( is_string( $field ) ) {
                    $field = array( $field );
                }
                array_unshift( $field, $field_id );
                call_user_func_array( array( $this, 'add_field' ), $field );
            }
        }
    }

    public function get_field( $field_id )
    {
        $field_key = sanitize_key( $field_id );
        return empty( $this->fields[$field_key] ) ? null : $this->fields[$field_key];
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
