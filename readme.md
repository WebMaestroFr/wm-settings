## Simple options pages for WordPress

Settings are really useful to provide an easy configuration of **themes** and **plugins** to our users within their administration panel, but the creation of options pages often ends up in a messy and repetitive use of the great *WordPress Settings API*.

Used as a library (or as a plugin), this class provides a **fast, clean and easy way to generate configuration pages**.

## Functions

```php
// Create the page
wm_create_settings_page( 'my_page_id', __( 'My Page' ), true, array(
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
) );
```

```php
// Access the values
$my_value = get_setting( 'my_setting_id', 'my_option_name' );
```

```php
// Create a "customize" section
create_customize_section( 'my_customize_setting_id', __( 'My Customize Setting' ), array(
    'my_customize_option_name' => array(
        'label'        => __( 'My Option' ),
        'description'  => __( 'This is my field description.' )
    )
) );
```

## Installation

1. Download the last release
2. Unzip it into your theme or plugin
3. `require_once( 'path/to/wm-settings/plugin.php' );`

## Documentation

[Read the documentation](http://webmaestro.fr/wordpress-settings-api-options-pages/#wm-settings-doc).

## Available field types

- **checkbox** – *0|1*
- **radio**, **select** – *choice key*
- **media** – *attachment id*
- **upload**, **image** – *attachment URL*
- **color** – *hexadecimal color*
- **textarea** – *raw text*
- **multi** (checkboxes) – associative array of *choice key* and *0|1*
- **action** – *PHP function call*
- **email**
- **url**
- **number**
- **text** or [any valid **HTML5 input type**](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Input#attr-type)

## Contributors

If you are interested by this project, please feel free to contribute in any way you like.

You can contact [@WebmaestroFR](https://twitter.com/WebmaestroFR) on twitter.
