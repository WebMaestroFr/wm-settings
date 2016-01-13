<?php

function wm_example()
{
	$page = wm_settings_add_page( 'page_id', 'Page Title' );
	$page->add_field( 'field_name', 'Field Label', 'text', array(
		'default' => 'Field default value.'
	) );
}
add_action( 'wm_settings_register_pages', 'wm_example' );


function wm_example_pages()
{
	$parent_page = wm_settings_add_page( 'parent_id', 'Parent Title', array(
		'parent'     => false,
		'title'      => 'Parent Menu',
		'capability' => 'manage_options',
		'icon_url'   => 'dashicons-wordpress',
		'position'   => '63.3'
	) );
	$child_page = wm_settings_add_page( 'child_id', 'Child Title', 'parent_id' );

	$options_page = wm_settings_add_page( 'example_page', 'Page Title', 'options-general.php', array(
		'description' => 'Page description. Lorem ipsum dolor sit amet.',
		'submit'      => 'Submit Text',
		'reset'       => 'Reset Text',
		'tabs'        => 'Main Tab Title',
		'updated'     => 'Success message.'
	), 'wm_example_fields' );
}
add_action( 'wm_settings_register_pages', 'wm_example_pages' );

function wm_example_fields( $page )
{
	$page->add_field( 'advanced', 'Advanced', 'text', array(
		'default'     => 'Field default value.',
		'sanitize'    => 'strtoupper'
	) );
	$page->add_field( 'custom', null, 'text', array(
		'description' => 'Field description. Lorem ipsum dolor sit amet.',
		'attributes'  => array(
			'placeholder' => 'Input Placeholder Attribute',
			'style'       => 'border: 1px solid #a00;'
		)
	) );

	$section = $page->add_section( 'example_section', 'Example Section', array(
		'description' => 'Section description. Lorem ipsum dolor sit amet.'
	) );

	$section->add_field( 'text_name', 'Text Label', 'text' );
	$section->add_field( 'checkbox_name', 'Checkbox Label', 'checkbox' );
	$section->add_field( 'textarea_name', 'Textarea Label', 'textarea' );
	$section->add_field( 'email_name', 'E-mail Label', 'email' );
	$section->add_field( 'url_name', 'URL Label', 'url' );
	$section->add_field( 'number_name', 'Number Label', 'number' );
	$section->add_field( 'color_name', 'Color Label', 'color' );

	$media_section = $page->add_section( 'media_section', 'Media Section' );

	$media_section->add_field( 'media_name', 'Media Label', 'media', array(
		'description' => 'Returns an attachment ID.'
	) );
	$media_section->add_field( 'upload_name', 'Upload Label', 'upload', array(
		'description' => 'Returns an attachment URL.'
	) );
	$media_section->add_field( 'image_name', 'Image Label', 'image', array(
		'description' => 'Returns an image URL.'
	) );

	$choices_section = $page->add_section( 'choices_section', 'Choices Section' );

	$choices_section->add_field( 'radio_name', 'Radio Label', 'radio', array(
		'choices' => array(
			'one'   => 'First Choice',
			'two'   => 'Second Choice',
			'three' => 'Third Choice'
		)
	) );
	$choices_section->add_field( 'select_name', 'Select Label', 'select', array(
		'choices' => array(
			'one'   => 'First Choice',
			'two'   => 'Second Choice',
			'three' => 'Third Choice'
		)
	) );
	$choices_section->add_field( 'multi_name', 'Multi Label', 'multi', array(
		'choices' => array(
			'one'   => 'First Choice',
			'two'   => 'Second Choice',
			'three' => 'Third Choice'
		)
	) );

	$action_section = $page->add_section( 'action_section', 'Action Section' );

	$action_section->add_field( 'action_name', 'Action Label', 'action' );
}

function wm_example_action()
{
	wp_send_json_success( 'Action success message.' );
}
add_action( 'wm_settings_ajax_action_name', 'wm_example_action' );

function wm_example_customize( $customize )
{
	$section = $customize->add_section( 'customize_section', 'Customize Section' );
	$section->add_field( 'field_name', 'Field Label' );
}
add_action( 'wm_settings_register_customize', 'wm_example_fields' );




	// $config = array(
	// 	'description' => 'Page description.',
	// 	'submit'      => 'Example Submit',
	// 	'reset'       => 'Example Reset',
	// 	'tabs'        => true,
	// 	'updated'     => 'Example settings saved.'
	// );
	// $page_fields = array(
	// 	'wm_example_text'     => array( 'Text Input Label', 'text', array(
    //         'description'    => 'Text input description.',
    //         'default'        => 'default value',
    //         'sanitize'       => 'strtoupper',
    //         'attributes'     => array(
	// 			'style'        => 'color: red;'
	// 		)
    //     ) ),
	// 	'wm_example_checkbox' => array( 'Checkbox Label', 'checkbox' ),
	// 	'wm_example_radio'    => array( 'Radio Buttons Label', 'radio', array(
	// 		'choices'        => array( 'One', 'Two', 'Three' )
	// 	) ),
	// 	'wm_example_select'   => array( 'Select Dropdown Label', 'select', array(
	// 		'choices'        => array( 'One', 'Two', 'Three' )
	// 	) ),
	// 	'wm_example_textarea' => array( 'Text Bloc Label', 'textarea' )
	// );
	// $page = wm_settings_add_page( 'wm_example_page', 'Example Page', $menu, $config, $page_fields );
	//
	// $page->add_section( 'wm_example_section', 'Example Section', array(
	// 	'description' => 'Section description.',
	// 	'submit'      => 'Section Submit',
	// 	'reset'       => 'Section Reset',
	// 	// 'customize'   => array()
	// ) );

// 	$child = wm_settings_add_page( 'my_child_page', 'My Child Page', 'my_parent_page', array(
// 		'description' => null,                                  // Page description
// 		'submit'      => __( 'Save Settings', 'wm-settings' ),  // Submit button text
// 		'reset'       => __( 'Reset Settings', 'wm-settings' ), // Reset button text (false to disable)
// 		'tabs'        => false,                                 // Use tabs to switch sections
// 		'updated'     => __( 'custom success message')
// 	) );
//
// 	$section_one = $parent->add_section( 'section_one', 'Section One', null, array(
// 		'field_1' => 'My field',
// 		'field_2' => array( 'My checkbox', 'checkbox' )
// 	) );
//
// 	$section_two = $parent->add_section( 'section_two', 'Section Two', array(
// 		'description' => 'Lorem ipsum dolor sit amet.',
// 		'submit'      => true,
// 		'reset'       => 'My Reset Text'
// 	), array(
// 		'field_3' => array( 'My textarea', 'textarea', array(
// 			'description' => 'Lorem ipsum dolor sit amet.',
// 			'default'     => 'My default.',
// 			'sanitize'    => 'strtoupper',
// 			'attributes'  => array(
// 				'style' => 'color: red'
// 			)
// 		) ),
// 		'field_4' => array( 'My multi', 'multi', array(
// 			// 'default'     => array( 'two' => 1 ),
// 			'choices'     => array( 'One', 'Two', 'Three' )
// 		) ),
// 		'field_5' => array( 'My action', 'action', array(
// 			'action'      => 'wp_send_json_success'
// 		) )
// 	) );
//
// 	$section_three = $child->add_section( 'section_three', 'Section Three', null, array(
// 		'field_6' => array( 'My color', 'color' ),
// 		'field_7' => array( 'My image', 'image' ),
// 		'field_8' => array( 'My media', 'media' )
// 	) );
// }
