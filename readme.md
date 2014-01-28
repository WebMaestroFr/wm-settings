## Synopsis

Based on the WordPress Settings API, this class generates options pages. It supports all basic input types, selects and all, but also media uploads, which is quite neat.

## Code Example

	$page_id = 'my_page_id';
	$page_title = __( 'My Page' );
	$menu = array(
		'parent'	=> 'plugins.php',
		'title'		=> __( 'My Page Menu' )
	);
	$fields = array(
		'my_setting_id'	=> array(
			'title'			=> __( 'My Section' ),
			'fields'	=> array(
				'my_option_name'	=> array(
					'type'			=> 'checkbox',
					'label'			=> __( 'My Option' ),
					'description'	=> __( 'This is the checkbox description text.' ),
					'default'		=> true
				),
				'other_option'		=> array(
					'label'	=> __( 'My Other Option' )
				)
			)
		)
	);
	$my_page = new WM_Settings( $page_id, $page_title, $menu, $fields );

## Motivation

The main idea was to simplify the creation of generic settings for themes and plugins. Make it quick and easy to set an options page.

## Installation

Use it as a WordPress plugin. Move its directory into `wp-content/plugins/`, and activate it on the plugin page.

## Documentation

You can read a bit more about this on [webmaestro.fr](http://webmaestro.fr/blog/wordpress-theme-options-page/).

## Tests

Uncomment `add_action( 'init', 'wm_settings_examples' );` (line 14) in wm-settings.php to enable the examples. You can then play within the examples.php file.

## Contributors

I would be really glad to see people getting interested by this project. Please feel free to contribute in anyway you feel. Contact @WebmaestroFR on twitter.

## License

[WTFPL](http://www.wtfpl.net/) – Do What the Fuck You Want to Public License