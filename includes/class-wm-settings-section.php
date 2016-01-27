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
        $setting_id,
        $notices = array();


    // SECTION CONSTRUCTOR

    public function __construct( $section_id, $title = null, array $config = null )
    {
        $this->section_id = sanitize_key( $section_id );
        $this->title = $title;

        $this->config  = array(
            'description' => null,
            'customize'   => null
        );
        if ( $config ) {
            $this->config = array_merge( $this->config, $config );
        }

        $this->setting_id = "wm_settings-{$section_id}";

        if ( ! get_option( $this->setting_id ) ) {
            // Initialise option with default values
            add_option( $this->setting_id, $this->sanitize_setting( false ) );
        }
    }


    // USER METHODS

    public function add_field( $field_id, $label = null, $type = 'text', array $config = array() )
    {
        $field_key = sanitize_key( $field_id );
        return $this->fields[$field_key] = new WM_Settings_Field( $this, $field_id, $label, $type, $config );
    }
    public function get_field( $field_id )
    {
        $field_key = sanitize_key( $field_id );
        return empty( $this->fields[$field_key] ) ? null : $this->fields[$field_key];
    }

    public function add_notice( $message, $type = 'info' )
    {
        $message = wpautop( trim( (string) $message ) );
        $this->notices[] = "<div class=\"wm-settings-notice {$type}\">{$message}</div>";
    }

    // Sanitize values before save
    public function sanitize_setting( $inputs = false )
    {
        // Array of sanitized values
        $values = array();

        // Section's fields
        foreach ( $this->fields as $name => $field ) {

            if ( false === $inputs ) {
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
        settings_errors( $this->setting_id );
        echo implode( '', array_unique( $this->notices ) );
        if ( $this->config['description'] ) {
            echo wpautop( $this->config['description'] );
        }
    }
}
