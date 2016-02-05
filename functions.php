<?php

/**
 * Public user functions
 *
 * @package WM_Settings
 */


/**
 * Get setting value.
 *
 * @since 2.0.0
 *
 * @param string $id Section identifier.
 * @param string $name Optional. Field name.
 *
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
 * @param string $page_id Page identifier.
 * @param string $title Optional. Page title.
 *                      Default $page_id.
 * @param boolean|string|array $menu {
 *     Optional. Menu parameters, false to disable the page, true for default top level, or a string for page parent slug.
 *
 *     @type string $parent Slug name of the parent menu.
 *                          Default 'options-general.php'.
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
 * @param array $config {
 *     Optional. An array of miscellaneous arguments.
 *
 *     @type string $description Page description.
 *     @type string $submit Text of the submit button.
 *                          Default 'Save Settings'.
 *     @type boolean $reset Whether to display the reset button or not.
 *                         Default false.
 *                         Accepts a string for custom text in place of the default 'Reset Settings'.
 *     @type boolean $tabs Whether to display the different sections as «tabs» or not.
 *                         Default false.
 *     @type string $updated Message of the success notice.
 *                           Default 'Settings saved.'.
 *                           Accepts false to disable the notice.
 * }
 * @param callable $register_func Optional. Function to register sections and fields in a later hook.
 *
 * @return WM_Settings_Page The page instance created.
 */
function wm_settings_add_page( $page_id, $title = null, $menu = true, array $config = null, $register_func = null )
{
    return WM_Settings::add_page( $page_id, $title, $menu, $config, $register_func );
}
