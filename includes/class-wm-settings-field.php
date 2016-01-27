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

        $this->id = "{$section->setting_id}-{$this->field_id}";
        $this->name = "{$section->setting_id}[{$this->field_id}]";
        $this->value = wm_get_setting( $section->section_id, $this->field_id );

        $this->config = array_merge( array(
            'description' => "",      // Page description
            'default'     => null,    // Default value
            'sanitize'    => null,    // Sanitation callback
            'attributes'  => array()  // HTML input attributes
        ), $config );

        switch ( $this->type ) {
            case 'textarea':
                if ( empty( $this->config['attributes']['rows'] ) ) {
                    $this->config['attributes']['rows'] = 5;
                }
                break;
            case 'color':
                // http://automattic.github.io/Iris/
                $this->config = array_merge( array(
            		'mode'     => 'hsl',
            		'controls' => array(
            	        'horiz' => 'h', // horizontal
            	        'vert'  => 's', // vertical
            	        'strip' => 'l'  // right strip
            	    ),
            		'hide'     => true,
                    'palettes' => false
                ), $this->config );
                break;
            case 'radio':
            case 'select':
            case 'multi':
                $this->config = array_merge( array(
                    'choices' => array()
                ), $this->config );
                break;
            case 'media':
            case 'image':
                $this->config = array_merge( array(
                    'title'    => $this->label,
                    'library'  => array(),
                    'button'   => array(
                        'text' => sprintf( __( 'Select %s', 'wm-settings' ), $this->label )
                    )
                ), $this->config, array(
                    'multiple' => false // TO DO
                ) );
                if ( 'image' === $this->type ) {
                    $this->config['library']['type'] = 'image';
                }
                break;
        }
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
                // Field option key
                return sanitize_key( $input );

            case 'media':
            case 'image':
                // Attachment id
                return absint( $input );

            case 'color':
                // Hex color
                return preg_match( '/^#[a-f0-9]{6}$/', $input )
                    ? $input
                    : "#0073aa";

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
                return esc_url_raw( $input );

            case 'number':
                return floatval( $input );

            default:
                return sanitize_text_field( $input );
        }
    }

    protected function get_attrs( array $attrs = array() )
    {
        $attrs = array_filter( array_merge( array(
            'id'   => $this->id,
            'name' => $this->name
        ), $attrs, $this->config['attributes'] ) );

        return implode( " ", array_map( function ( $k, $v ) {
            return " {$k}=\"{$v}\"";
        }, array_map( 'sanitize_key', array_keys( $attrs ) ), array_map( 'esc_attr', $attrs) ) );
    }

    protected function get_description()
    {
        return empty( $this->config['description'] ) ? $this->config['description'] : "<p class=\"description\">{$this->config['description']}</p>";
    }

    public function render( $return = false )
    {
        echo "<div class=\"wm-settings-{$this->type}\">";

        switch ( $this->type )
        {
            // Checkbox
            case 'checkbox':
                $attrs = $this->get_attrs( array(
                    'type'  => 'checkbox',
                    'value' => 1
                ) );
                $checked = checked( 1, $this->value, false );

                echo "<label><input {$attrs} {$checked} /> {$this->config['description']}</label>";

                break;

            // Radio options
            case 'radio':
                echo "<fieldset id=\"{$this->id}\">";
                echo implode( '<br />', array_map( function ( $k, $v ) {
                    $attrs = $this->get_attrs( array(
                        'type'  => 'radio',
                        'value' => $k,
                        'id'    => null
                    ) );
                    $checked = checked( $k, $this->value, false );
                    return "<label><input {$attrs} {$checked} /> {$v}</label>";
                }, array_keys( $this->config['choices'] ), $this->config['choices'] ) );
                echo "</fieldset>";
                echo $this->get_description();

                break;

            // Select options
            case 'select':
                $attrs = $this->get_attrs();

                $choices = implode( '', array_map( function ( $k, $v ) {
                    $selected = selected( $k, $this->value, false );
                    return "<option value=\"{$k}\" {$selected}>{$v}</option>";
                }, array_keys( $this->config['choices'] ), $this->config['choices'] ) );

                echo "<select {$attrs}>{$choices}</select>";
                echo $this->get_description();

                break;

            // Media upload button
            case 'media':
            case 'image':
                $attrs = $this->get_attrs( array(
                    'type'       => 'hidden',
                    'value'      => $this->value,
                    'data-frame' => json_encode( array_filter( $this->config, function( $key ) {
                        return in_array( $key, array( 'title', 'multiple', 'library', 'button' ) );
                    }, ARRAY_FILTER_USE_KEY ) ),
                    'class'      => 'wm-settings-media-input'
                ) );
                if ( $this->value && $value_src = wp_get_attachment_image_src( $this->value, 'full', true ) ) {
                    $preview_src = $value_src[0];
                    $preview_title = get_the_title( $this->value );
                } else {
                    $preview_src = "";
                    $preview_title = "";
                }
                $remove_text = __( 'Remove', 'wm-settings' );

                echo "<div class=\"wm-settings-media-preview\">";
                echo "<div class=\"centered\"><img class=\"wm-settings-media-image\" src=\"{$preview_src}\" alt=\"{$preview_title}\" /></div>";
                echo "<span class=\"wm-settings-media-title\">{$preview_title}</span>";
                echo "</div>";
                echo "<div class=\"actions\">";
                echo "<input type=\"button\" class=\"button wm-settings-media-select\" value=\"{$this->config['button']['text']}\" />";
                echo "<input type=\"button\" class=\"button wm-settings-media-remove\" value=\"{$remove_text}\" />";
                echo "</div>";
                echo "<input {$attrs} />";
                echo $this->get_description();

                break;

            // Text bloc
            case 'textarea':
                $attrs = $this->get_attrs( array(
                    'class' => 'large-text'
                ) );

                echo "<textarea {$attrs}>{$this->value}</textarea>";
                echo $this->get_description();

                break;

            // Multiple checkboxes
            case 'multi':
                echo "<fieldset id=\"{$this->id}\">";
                echo implode( '<br />', array_map( function ( $k, $v ) {
                    $attrs = $this->get_attrs( array(
                        'type'  => 'checkbox',
                        'value' => 1,
                        'id'    => null,
                        'name'  => "{$this->name}[{$k}]"
                    ) );
                    $checked = checked( 1, $this->value[$k], false );
                    return "<label><input {$attrs} {$checked} /> {$v}</label>";
                }, array_keys( $this->config['choices'] ), $this->config['choices'] ) );
                echo "</fieldset>";
                echo $this->get_description();

                break;

            // Ajax action button
            case 'action':
                $attrs = $this->get_attrs( array(
                    'type'        => 'button',
                    'class'       => 'button button-large wm-settings-action-button',
                    'value'       => $this->label,
                    'data-action' => $this->field_id,
                    'name'        => null
                ) );
                $spinner_src = admin_url( "images/spinner.gif" );
                $spinner_alt = __( 'Loading', 'wm-settings' );

                echo "<div class=\"wm-settings-notice\"></div>";
                echo "<input {$attrs} />";
                echo "<img class=\"wm-settings-action-spinner\" src=\"{$spinner_src}\" alt=\"{$spinner_alt}\" />";
                echo $this->get_description();

                break;

            // Color picker
            case 'color':
                $attrs = $this->get_attrs( array(
                    'type'        => 'text',
                    'value'       => $this->value,
                    'class'       => 'wm-settings-color-input',
                    'data-picker' => json_encode( array_filter( $this->config, function( $key ) {
                        return in_array( $key, array( 'mode', 'controls', 'hide', 'border', 'width', 'palettes' ) );
                    }, ARRAY_FILTER_USE_KEY ) )
                ) );

                echo "<input {$attrs} />";
                echo $this->get_description();

                break;

            // HTML5 input ("text", "email"...)
            default:
                $attrs = $this->get_attrs( array(
                    'type'  => $this->type,
                    'value' => $this->value,
                    'class' => 'regular-text'
                ) );

                echo "<input {$attrs} />";
                echo $this->get_description();

                break;
        }

        echo "</div>";
    }
}
