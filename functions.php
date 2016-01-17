<?php

/**
 * Get setting value.
 *
 * @since 2.0.0
 *
 * @param string $id Section identifier.
 * @param string $name Optional. Field name.
 * @return array|mixed|null Returns all the values of a section, the single value of a field if specified, or null if nothing found.
 */
function wm_get_setting( $id, $name = null )
{
    // Cache values
    static $values = array();

    if ( ! $id = sanitize_key( $id ) ) {
        return null;
    }

    if ( ! array_key_exists( $id, $values ) ) {
        $values[$id] = get_option( "wm_settings-{$id}" );
    }
    if ( empty( $values[$id] ) ) {
        return null;
    }
    if ( $name ) {
        // Field value
        return array_key_exists( $name, $values[$id] ) ? $values[$id][$name] : null;
    }
    // Section values
    return $values[$id];
}


/**
 * Register a configuration page and its menu.
 *
 * @since 2.0.0
 *
 * @param string $name Page identifier.
 * @param string $title Optional. Page title.
 * @param boolean|array $menu {
 *     Optional. Menu parameters, false to disable the page.
 *
 *     @type string $parent Slug name of the parent menu.
 *                          Default 'themes.php'.
 *                          Accepts false to create a top level menu item.
 *     @type string $title Menu item label.
 *                         Default $page_title.
 *     @type string $capability Capability required for this menu to be displayed to the user.
 *                              Default 'manage_options'.
 *     @type string $icon_url Menu icon (for top level menu item).
 *                            Default 'dashicons-admin-generic'.
 *     @type integer $position Position in the menu order this menu should appear (for top level menu item).
 *                             Default bottom of menu structure.
 * }
 * @param array $sections {
 *     Optional. Sections list.
 *
 *     @type callable|array $section_id {
 *         Section declaration. Can be returned by callback.
 *
 *         @type string $title Optional. Section title.
 *         @type string $description Optional. Section description.
 *         @type array $fields {
 *             Optional. Setting declaration.
 *
 *             @type callable|string|array $field_name {
 *                 Field declaration. Used as label if string.
 *
 *                 @type string $type Type.
 *                                    Default 'text'.
 *                                    Accepts 'checkbox', 'textarea', 'radio', 'select', 'multi', 'media', 'action', 'color' or any valid HTML5 input type attribute.
 *                 @type string $label Optional. Label.
 *                                     Accepts false to hide the label column on this field.
 *                 @type string $description Optional. Description.
 *                 @type string $default Optional. Default value.
 *                 @type callable $sanitize Optional. Function to apply in place of the default sanitation.
 *                                          Receive the input $value and the option $name as parameters, and is expected to return a properly sanitised value.
 *                 @type array $attributes Optional. Associative array( 'name' => value ) of HTML attributes.
 *                 @type array $choices Only for 'radio', 'select' and 'multi' field types. Associative array( 'key' => label ) of options.
 *                 @type callable $action Only for 'action' field type. A callback responding with either wp_send_json_success (success) or wp_send_json_error (failure),
 *                                        where the optional sent $data is either the message to display (string), or array( 'reload' => true ) if the page needs to reload on action's success.
 *             }
 *         }
 *         @type boolean $customize Optional. Whether to display this section in the "Customizer".
 *                                  Default false.
 *     }
 * }
 * @param array $config {
 *     Optional. An array of miscellaneous arguments.
 *
 *     @type boolean $tabs Whether to display the different sections as «tabs» or not.
 *                         There must be several sections, and they must have a title.
 *                         Default false.
 *     @type string $submit Text of the submit button.
 *                          Default 'Save Settings'.
 *     @type string $reset Text of the reset button.
 *                         Default 'Reset Settings'.
 *                         Accepts false to disable the button.
 *     @type string $description Page description.
 *     @type string $updated Message of the success notice.
 *                           Default 'Settings saved.'.
 *                           Accepts false to disable the notice.
 * }
 * @return WM_Settings The page instance created.
 */
function wm_settings_add_page( $page_id, $title = null, $menu = true, array $config = null, $fields = null )
{
    return WM_Settings::add_page( $page_id, $title, $menu, $config, $fields );
}


/**
 * Register a customize section.
 *
 * @since 2.0.0
 *
 * @param string $section_id Section identifier.
 * @param string $title Optional. Section title.
 * @param array $fields {
 *     Optional. Setting declaration.
 *
 *     @type string|array $field_name {
 *         Field declaration. Used as label if string.
 *
 *         @type string $type Type.
 *                            Default 'text'.
 *                            Accepts 'checkbox', 'textarea', 'radio', 'select', 'multi', 'media', 'action', 'color' or any valid HTML5 input type attribute.
 *         @type string $label Optional. Label.
 *                             Accepts false to hide the label column on this field.
 *         @type string $description Optional. Description.
 *         @type string $default Optional. Default value.
 *         @type callable $sanitize Optional. Function to apply in place of the default sanitation.
 *                                  Receive the input $value and the option $name as parameters, and is expected to return a properly sanitised value.
 *         @type array $attributes Optional. Associative array( 'name' => value ) of HTML attributes.
 *         @type array $choices Only for 'radio', 'select' and 'multi' field types. Associative array( 'key' => label ) of options.
 *         @type callable $action Only for 'action' field type. A callback responding with either wp_send_json_success (success) or wp_send_json_error (failure),
 *                                where the optional sent $data is either the message to display (string), or array( 'reload' => true ) if the page needs to reload on action's success.
 *     }
 * }
 * @param string $description Optional. Section description.
 * @return WM_Settings The page instance created.
 */
function wm_add_customize_section( $section_id = 'custom_section', $title = null, array $fields = null, $description = null )
{
    return new WM_Settings( "customize_{$section_id}", $title, false, array(
        $section_id => array(
            'title'       => $title,
            'description' => $description,
            'fields'      => $fields,
            'customize'   => true
        )
    ) );
}
