<?php

/**
 * Settings section class
 *
 * @since      2.0.0
 * @package    WM_Settings
 * @subpackage WM_Settings/includes
 */

class WM_Settings_Section
{
    public $section_id,     // Section id
        $title,             // Section title
        $config,            // Section configuration
        $fields = array(),  // Section's fields list
        $setting_id,        // Section's setting id (for WP Settings API)
        $notices = array(); // Section notice messages


    /**
     * Section constructor.
     *
     * Register a configuration section.
     *
     * @since 2.0.0
     *
     * @see WM_Settings_Page::add_section
     */
    public function __construct( $section_id, $title = null, $config = null )
    {
        $this->section_id = sanitize_key( $section_id );
        $this->title = $title;

        $this->config = array(
            'description' => is_string( $config ) ? $config : null
        );
        if ( is_array( $config ) ) {
            $this->config = array_merge( $this->config, $config );
        }

        $this->setting_id = "wm_settings-{$section_id}";

        if ( ! get_option( $this->setting_id ) ) {
            // Initialise option with default values
            add_option( $this->setting_id, $this->sanitize_setting( false ) );
        }
    }


    // USER METHODS

    /**
     * Add a configuration field.
     *
     * @since 2.0.0
     *
     * @param string $field_id Field identifier.
     * @param string $label Optional. Field label.
     *                      Accepts null to disable.
     * @param string $type Optional. Type.
     *                     Default 'text'.
     *                     Accepts 'text', 'checkbox', 'textarea', 'radio', 'select', 'multi', 'media', 'image', 'action', 'color' or any valid HTML5 input type attribute.
     * @param array $config {
     *     Optional. Field configuration.
     *
     *     @type string $description Optional. Description.
     *     @type string $default Optional. Default value.
     *     @type callable $sanitize Optional. Function to apply in place of the default sanitation.
     *                              Receive the input $value and the option $name as parameters, and is expected to return a properly sanitised value.
     *     @type array $attributes Optional. Associative array( 'name' => value ) of HTML attributes.
     *     @type array $choices Only for 'radio', 'select' and 'multi' field types. Associative array( 'key' => label ) of options.
     * }
     *
     * @return WM_Settings_Field Returns the field instance.
     */
    public function add_field( $field_id, $label = null, $type = 'text', array $config = array() )
    {
        $field_key = sanitize_key( $field_id );
        return $this->fields[$field_key] = new WM_Settings_Field( $this, $field_id, $label, $type, $config );
    }

    /**
     * Get setting field.
     *
     * @since 2.0.0
     *
     * @param string $field_id Field identifier.
     *
     * @return WM_Settings_Field Returns the field instance, or null if not found.
     */
    public function get_field( $field_id )
    {
        $field_key = sanitize_key( $field_id );
        return empty( $this->fields[$field_key] ) ? null : $this->fields[$field_key];
    }

    /**
     * Add an admin notice to the section.
     *
     * @since 2.0.0
     *
     * @param string $message Notice message.
     * @param string $type Optional. Alert type.
     *                     Default 'info'.
     *                     Accepts 'info', 'error', 'success', 'warning'.
     */
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
