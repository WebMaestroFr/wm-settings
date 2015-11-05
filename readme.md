## Synopsis

Based on the WordPress Settings API, a class to generate options pages. Create settings forms with all basic input types, selects, textareas and media uploads.

## Basic Example

```php
// Define the page
$my_page = create_settings_page(
  'my_page_id',
  __( 'My Page' ),
  array(
    'title' => __( 'My Menu' )
  ),
  array(
    'my_setting_id' => array(
      'title'       => __( 'My Setting' ),
      'description' => __( 'This is my section description.' ),
      'fields'      => array(
        'my_option_name' => array(
          'label'        => __( 'My Option' ),
          'description'  => __( 'This is my field description.' )
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

1. Download the last release
2. Unzip it into your theme or plugin
3. `require_once( 'path/to/wm-settings/plugin.php' );`

## Documentation

[Read the documentation](http://webmaestro.fr/wordpress-settings-api-options-pages/#wm-settings-doc).

## Contributors

If you are interested by this project, please feel free to contribute in any way you like.

You can contact [@WebmaestroFR](https://twitter.com/WebmaestroFR) on twitter.

## License

[WTFPL](http://www.wtfpl.net/) – Do What the Fuck You Want to Public License
