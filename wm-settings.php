<?php
/*
Plugin Name: WebMaestro Settings
Plugin URI: http://#
Author: Etienne Baudry
Author URI: http://webmaestro.fr
Description: Simplified options system for WordPress. Generates a default page for settings.
Version: 1.0
License: GNU General Public License
License URI: license.txt
Text Domain: wm_settings
*/

// add_action( 'init', 'wm_settings_examples' );
function wm_settings_examples() {
	include_once( plugin_dir_path( __FILE__ ) . 'examples.php' );
}

function wm_get_option( $setting, $option = false )
{
	$setting = get_option( $setting );
	if ( ! $option ) {
		return $setting;
	}
	if ( ! $setting || ! isset( $setting[$option] ) ) {
		return false;
	}
	return $setting[$option];
}

class WM_Settings {

	private $page,
			$title,
			$menu,
			$settings = array();

	public function __construct( $page = 'custom_settings', $title = null, $menu = array(), $settings = array() )
	{
		$this->page   = $page;
		$this->title  = $title ? $title : __( 'Custom Settings', 'wm_settings' );
		$this->menu   = is_array( $menu ) ? array_merge( array(
			'parent'		=> 'themes.php',
			'title'			=> $this->title,
			'capability'	=> 'manage_options',
			'icon_url'		=> null,
			'position'		=> null
		), $menu ) : false;
		$this->apply_settings( $settings );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function apply_settings( $settings )
	{
		foreach ( $settings as $setting => $section ) {
			$section = array_merge( array(
				'title'			=> null,
				'description'	=> null,
				'fields'		=> array(),
				'values'		=> null
			), $section );
			foreach ( $section['fields'] as $name => $field ) {
				$field = array_merge( array(
					'type'			=> 'text',
					'label'			=> null,
					'description'	=> null,
					'default'		=> null,
					'callback'		=> null,
					'attributes'	=> array(),
					'options'		=> null
				), $field );
				$section['fields'][$name] = $field;
			}
			$this->settings[$setting] = $section;
			if ( ! get_option( $setting ) ) {
				add_option( $setting, $this->get_defaults( $setting ) );
			}
		}
	}

	private function get_defaults( $setting )
	{
		$defaults = array();
		foreach ( $this->settings[$setting]['fields'] as $name => $field ) {
			$defaults[$name] = $field['default'];
		}
		return $defaults;
	}

	public function admin_menu()
	{
		if ( $this->menu ) {
			if ( $this->menu['parent'] ) {
				$page = add_submenu_page( $this->menu['parent'], $this->title, $this->menu['title'], $this->menu['capability'], $this->page, array( $this, 'do_page' ) );
			} else {
				$page = add_menu_page( $this->title, $this->menu['title'], $this->menu['capability'], $this->page, array( $this, 'do_page' ), $this->menu['icon_url'], $this->menu['position'] );
				if ( $this->title !== $this->menu['title'] ) {
					add_submenu_page( $this->page, $this->title, $this->title, $this->menu['capability'], $this->page );
				}
			}
			add_action( 'load-' . $page, array( __CLASS__, 'load_page' ) );
		}
	}

	public function admin_init()
	{
		foreach ( $this->settings as $setting => $section ) {
			register_setting( $this->page, $setting, array( $this, 'sanitize_setting' ) );
			add_settings_section( $setting, $section['title'], array( $this, 'do_section' ), $this->page );
			$values = $section['values'] ? $section['values'] : get_option( $setting );
			foreach ( $section['fields'] as $name => $field ) {
				$field = array_merge( array(
					'id'		=> "{$setting}_{$name}",
					'name'		=> "{$setting}[{$name}]",
					'value'		=> $values[$name],
					'label_for'	=> $field['id']
				), $field );
				add_settings_field( $name, $field['label'], array( __CLASS__, 'do_field' ), $this->page, $setting, $field );
			}
		}
		if ( isset( $_POST["{$this->page}_reset"] ) ) {
			$this->reset();
		}
	}

	public static function load_page()
	{
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
	}

	public static function admin_enqueue_scripts()
	{
		wp_enqueue_media();
		wp_enqueue_script( 'wm-settings', plugins_url( 'wm-settings.js' , __FILE__ ), array( 'jquery' ) );
	}

	private function reset()
	{
		foreach ( $this->settings as $setting => $section ) {
			$_POST[$setting] = array_merge( $_POST[$setting], $this->get_defaults( $setting ) );
		}
		add_settings_error( $this->page, 'settings_reset', __( 'Default settings have been reset.' ), 'updated' );
	}

	public function do_page()
	{ ?>
		<div class="wrap">
			<h2><?php echo $this->title; ?></h2>
			<?php settings_errors(); ?>
			<form action="options.php" method="POST" enctype="multipart/form-data">
				<?php
					settings_fields( $this->page );
					do_settings_sections( $this->page );
					submit_button();
					submit_button( __( 'Reset Settings' ), 'small', "{$this->page}_reset", true, array( 'onclick' => "return confirm('" . __( 'Do you really want to reset all these settings to their default values ?', 'wm_settings' ) . "');" ) );
				?>
			</form>
		</div>
	<?php }

	public function do_section( $args )
	{
		extract( $args );
		if ( $text = $this->settings[$id]['description'] ) {
			echo wpautop( $text );
		}
		echo "<input name='{$id}[{$this->page}_setting]' type='hidden' value='{$id}' />";
	}

	public static function do_field( $args )
	{
		extract( $args );
		$attrs = "name='{$name}'";
		foreach ( $attributes as $k => $v ) {
			$k = sanitize_key( $k );
			$v = esc_attr( $v );
			$attrs .= " {$k}='{$v}'";
		}
		$desc = $description ? "<p class='description'>{$description}</p>" : '';
		switch ( $type )
		{
			case 'checkbox':
				$check = checked( 1, $value, false);
				echo "<label><input {$attrs} id='{$id}' type='checkbox' value='1' {$check} />";
				if ( $description ) { echo " {$description}"; }
				echo "</label>";
				break;

			case 'radio':
				if ( ! $options ) { break; }
				echo "<fieldset id='{$id}'>";
				foreach ( $options as $v => $label ) {
					$check = checked( $v, $value, false );
					$options[$v] = "<label><input {$attrs} type='radio' value='{$v}' {$check} /> {$label}</label>";
				}
				echo implode( '<br />', $options );
				echo "{$desc}</fieldset>";
				break;

			case 'select':
				if ( ! $options ) { break; }
				echo "<select {$attrs} id='{$id}'>";
				foreach ( $options as $v => $label ) {
					$select = selected( $v, $value, false );
					echo "<option value='{$v}' {$select} />{$label}</option>";
				}
				echo "</select>{$desc}";
				break;

			case 'media':
				echo "<fieldset class='wm-settings-media' id='{$id}'><input {$attrs} type='hidden' value='{$value}' />";
				if ( $value ) {
					echo wp_get_attachment_image( $value, 'medium' );
				}
				echo "<p><a class='button button-large wm-select-media' title='{$label}'>" . sprintf( __( 'Select %s', 'wm_settings' ), $label ) . "</a> ";
				echo "<a class='button button-small wm-remove-media' title='{$label}'>" . sprintf( __( 'Remove %s', 'wm_settings' ), $label ) . "</a></p></fieldset>";
				break;

			case 'textarea':
				echo "<textarea {$attrs} id='{$id}' class='large-text'>{$value}</textarea>{$desc}";
				break;

			default:
				echo "<input {$attrs} id='{$id}' type='{$type}' value='{$value}' class='regular-text' />{$desc}";
				break;
		}
	}

	public function sanitize_setting( $inputs )
	{
		$outputs = array();
		$setting = $inputs["{$this->page}_setting"];
		foreach ( $this->settings[$setting]['fields'] as $name => $field ) {
			$input = array_key_exists( $name, $inputs ) ? $inputs[$name] : null;
			switch ( $field['type'] )
			{
				case 'checkbox':
					$output = $input ? 1 : 0;
					break;

				case 'radio':
				case 'select':
					$output = sanitize_key( $input );
					break;

				case 'media':
					$output = absint( $input );
					break;

				case 'email':
					$output = sanitize_email( $input );
					break;

				case 'url':
					$output = esc_url_raw( $input );
					break;

				case 'number':
					$output = floatval( $input );
					break;

				default:
					$output = sanitize_text_field( $input );
					break;
			}
			if ( $field['callback'] ) {
				$output = call_user_func( $field['callback'], $output );
			}
			$outputs[$name] = $output;
		}
		return $outputs;
	}
}
