## Synopsis

Based on the WordPress Settings API, this class generates options pages. It supports all basic input types, selects and all, but also media uploads, which is quite neat.

## Basic Example

```php
$my_page = create_settings_page(
  'my_page_id',
  __( 'My Page' ),
  array(
    'title'   => __( 'My Menu' ),
    'parent'  => 'themes.php'
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
```

## Motivation

The main idea was to simplify the creation of generic settings for themes and plugins. Make it quick and easy to set an options page.

## Installation

1. Download the latest version
2. Unzip, and rename the `wm-settings-master` folder to `wm-settings`
3. Move it into your `wp-content/plugins` directory
4. Activate the plugin in WordPress

## Documentation

You can read a bit more about this on [webmaestro.fr](http://webmaestro.fr/blog/wordpress-theme-options-page/).

## Tests

Include and modify the `examples.php`.

## Contributors

I would be really glad to see people getting interested by this project. Please feel free to contribute in anyway you feel. Contact @WebmaestroFR on twitter.

## License

[WTFPL](http://www.wtfpl.net/) – Do What the Fuck You Want to Public License
