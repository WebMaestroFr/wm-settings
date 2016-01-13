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
