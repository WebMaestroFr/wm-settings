<?php

/**
 * Instanciate settings pages sections.
 *
 * @since 2.0.0
 */
class WM_Settings_Field
{
    public $field_id,
        $label,
        $type,
        $name,
        $value,
        $config;

    public function __construct( $section, $field_id, $label = null, $type = 'text', array $config = array() )
    {
        $this->field_id = sanitize_key( $field_id );
        $this->label = $label;
        $this->type = $type;

        $this->name = "{$section->setting_id}[{$this->field_id}]";
        $this->value = wm_get_setting( $section->section_id, $this->field_id );

        $this->config = array_merge( array(
            'description' => null,               // Page description
            'default'     => null,               // Default value
            'sanitize'    => null,               // Sanitation callback
            'attributes'  => array(),            // HTML input attributes
            'choices'     => null,               // Options list (for "radio", "select" or "multi" types)
            'action'      => null                // Callback function (for "action" type)
        ), $config );
    }

    public function sanitize_value( $input )
    {
        // "Custom" sanitation
        if ( $this->config['sanitize'] ) {
            return call_user_func( $this->config['sanitize'], $input );
        }
        // "Default" sanitation
        switch ( $this->type )
        {
            case 'checkbox':
                // Boolean
                return $input ? 1 : 0;

            case 'radio':
            case 'select':
            case 'dropdown-pages':
                // Field option key
                return sanitize_key( $input );

            case 'media':
                // Attachment id
                return absint( $input );

            case 'color':
                // Hex color
                return preg_match( '/^#[a-f0-9]{6}$/', $input )
                    ? $input
                    : "#808080";

            case 'textarea':
                $value = "";
                // Convert and keep new lines and tabulations
                $nl = "WM-SETTINGS-NEW-LINE";
                $tb = "WM-SETTINGS-TABULATION";
                $lines = explode( $nl, sanitize_text_field( str_replace( "\t", $tb, str_replace( "\n", $nl, $input ) ) ) );
                foreach ( $lines as $line ) {
                    $value .= str_replace( $tb, "\t", trim( $line ) ) . "\n";
                }
                return trim( $value );

            case 'multi':
                $value = array();
                foreach ( array_keys( $this->config['choices'] ) as $key ) {
                    $value[$key] = empty( $input[$key] ) ? 0 : 1;
                }
                return $value;

            case 'action':
                return null;

            case 'email':
                return sanitize_email( $input );

            case 'url':
            case 'image':
            case 'upload':
                return esc_url_raw( $input );

            case 'number':
                return floatval( $input );

            default:
                return sanitize_text_field( $input );
        }
    }

    // Control display callback
    public function render()
    {
        // HTML attributes
        $attrs = "name=\"{$this->name}\"";
        foreach ( $this->config['attributes'] as $k => $v ) {
            $k = sanitize_key( $k );
            $v = esc_attr( $v );
            $attrs .= " {$k}=\"{$v}\"";
        }
        // Field description "paragraphed"
        $desc = $this->config['description'] ? "<p class=\"description\">{$this->config['description']}</p>" : '';
        // Display control
        switch ( $this->type )
        {
            // Checkbox
            case 'checkbox':
                $check = checked( 1, $this->value, false );
                echo "<label id=\"{$this->name}\"><input {$attrs} type=\"checkbox\" value=\"1\" {$check} />";
                if ( $this->config['description'] ) {
                    echo " {$this->config['description']}";
                }
                echo "</label>";
                break;
            // Radio options
            case 'radio':
                if ( ! is_array( $this->config['choices'] ) ) {
                    _e( 'No options defined.', 'wm-settings' );
                } else {
                    echo "<fieldset id=\"{$this->name}\">";
                    foreach ( $this->config['choices'] as $v => $this->label ) {
                        $check = checked( $v, $this->value, false );
                        $this->config['choices'][$v] = "<label><input {$attrs} type=\"radio\" value=\"{$v}\" {$check} /> {$this->label}</label>";
                    }
                    echo implode( '<br />', $this->config['choices'] );
                    echo "{$desc}</fieldset>";
                }
                break;
            // Select options
            case 'select':
                if ( ! is_array( $this->config['choices'] ) ) {
                    _e( 'No options defined.', 'wm-settings' );
                } else {
                    echo "<select {$attrs} id=\"{$this->name}\">";
                    foreach ( $this->config['choices'] as $v => $this->label ) {
                        $select = selected( $v, $this->value, false );
                        echo "<option value=\"{$v}\" {$select} />{$this->label}</option>";
                    }
                    echo "</select>{$desc}";
                }
                break;
            // Media upload button
            case 'media':
            case 'upload':
            case 'image':
                $v = $this->value ? esc_attr( $this->value ) : '';
                echo "<fieldset class=\"wm-settings-media\" data-type=\"{$this->type}\" id=\"{$this->name}\">";
                if ( 'upload' === $this->type ) {
                    echo "<input {$attrs} type=\"text\" value=\"{$v}\" class=\"disabled regular-text\" />";
                } else {
                    echo "<input {$attrs} type=\"hidden\" value=\"{$v}\" />";
                    if ( $this->value && 'media' === $this->type ) {
                        $src = wp_get_attachment_image_src( $this->value, 'full', true );
                        $v = $src[0];
                    }
                    echo "<p><img class=\"wm-preview-media\" src=\"{$v}\"></p>";
                }
                $select_text = sprintf( __( 'Select %s', 'wm-settings' ), $this->label );
                echo "<p><a class=\"button button-large wm-select-media\" title=\"{$this->label}\">{$select_text}</a> ";
                $remove_text = sprintf( __( 'Remove %s', 'wm-settings' ), $this->label );
                echo "<a class=\"button button-small wm-remove-media\" title=\"{$this->label}\">{$remove_text}</a></p>";
                echo "{$desc}</fieldset>";
                break;
            // Text bloc
            case 'textarea':
                echo "<textarea {$attrs} id=\"{$this->name}\" class=\"large-text\">{$this->value}</textarea>{$desc}";
                break;
            // Multiple checkboxes
            case 'multi':
                if ( ! is_array( $this->config['choices'] ) ) {
                    _e( 'No options defined.', 'wm-settings' );
                } else {
                    echo "<fieldset id=\"{$this->name}\">";
                    foreach ( $this->config['choices'] as $n => $this->label ) {
                        $a = preg_replace( "/name\=\"(.+)\"/", "name=\"$1[{$n}]\"", $attrs );
                        $check = checked( 1, $this->value[$n], false );
                        $this->config['choices'][$n] = "<label><input {$a} type=\"checkbox\" value=\"1\" {$check} /> {$this->label}</label>";
                    }
                    echo implode( '<br />', $this->config['choices'] );
                    echo "{$desc}</fieldset>";
                }
                break;
            // Ajax action button
            case 'action':
                echo "<p class=\"wm-settings-action\" id=\"{$this->name}\"><input {$attrs} type=\"button\" class=\"button button-large\" value=\"{$this->label}\" data-action=\"{$this->field_id}\" /></p>{$desc}";
                break;
            // Color picker
            case 'color':
                $v = esc_attr( $this->value );
                echo "<input {$attrs} id=\"{$this->name}\" type=\"text\" value=\"{$v}\" class=\"wm-settings-color\" />{$desc}";
                break;
            // HTML5 input ("text", "email"...)
            default:
                $v = esc_attr( $this->value );
                echo "<input {$attrs} id=\"{$this->name}\" type=\"{$this->type}\" value=\"{$v}\" class=\"regular-text\" />{$desc}";
                break;
        }
    }
}
