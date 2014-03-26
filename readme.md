## Synopsis

Based on the WordPress Settings API, a class to generate options pages. Create settings forms with all basic input types, selects, textareas and media uploads.

## Basic Example

```php
$my_page = create_settings_page(
  'my_page_id',
  __( 'My Page' ),
  array(
    'title'   => __( 'My Menu' ),
    'parent'  => 'themes.php' // As a submenu of "Appearance"
  ),
  array(
    'my_setting_id' => array(
      'title'     => __( 'My Setting' ),
      'description'   => __( 'This is my section description.' ),
      'fields'    => array(
        'my_option_name'    => array(
          'label'         => __( 'My Option' ),
          'description'   => __( 'This is my field description.' )
        )
      )
    )
  )
);

// Access the values
$my_value = get_setting( 'my_setting_id', 'my_option_name' );
```

## Motivation

Settings are really useful to provide an easy configuration of themes and plugins to our users within their administration panel. But the creation of options pages often ends up in a messy and repetitive use of the great WordPress Settings API.

Considering generic form fields, this is a class to clean and simplify the process. It’s something light that shall be used on the admin side.

## Installation

1. Download the latest version
2. Then, you can include the class into your WordPress Plugin or Theme directory, and call it before admin_init.
  ```php
  function my_theme_or_plugin_init()
  {
    require_once( plugin_dir_path( __FILE__ ) . 'libs/wm-settings/wm-settings.php' );
  }
  add_action( 'init', 'my_theme_or_plugin_init' );
  ```
  Or, you can install the class as a plugin.
  1. Unzip, and rename the wm-settings-master folder to wm-settings
  2. Move it into your wp-content/plugins directory
  3. Activate the plugin in WordPress

## Documentation

[Read the documentation](http://webmaestro.fr/wordpress-settings-api-options-pages/#wm-settings-doc).

## Contributors

If you are interested by this project, please feel free to contribute in any way you like.

You can contact [@WebmaestroFR](https://twitter.com/WebmaestroFR) on twitter.

## License

[WTFPL](http://www.wtfpl.net/) – Do What the Fuck You Want to Public License
