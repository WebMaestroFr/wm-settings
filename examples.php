<?php

// __________________________________________________________

// A really minimal declaration, to show the basic defaults :

$my_page = new WM_Settings(
  'my_page_id',
  __( 'My Page' ),
  array(
    // http://codex.wordpress.org/Function_Reference/add_menu_page#Parameters
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

// Visit this example at /wp-admin/themes.php?page=my_page_id

// Now to access "My Option" value :
// $value = wm_get_option( 'my_setting_id', 'my_option_name' );
// Or :
// $setting = get_option( 'my_setting_id' ); $value = $setting['my_option_name'];


// __________________________________________________________

// A top level page :
$my_top_page = new WM_Settings(
  'my_top_level_page',
  __( 'My Top Level Page' ),
  array(
    'parent'        => false,
    'title'         => __( 'Top Level Menu' ),
    'capability'    => 'manage_options',
    'icon_url'      => 'dashicons-admin-generic',
    'position'      => '63.3'
  )
);

// And a sub-page, with fields pre-declared :
$my_sub_page = new WM_Settings(
  'my_sub_page',
  __( 'My Sub Page' ),
  array(
    'parent'    => 'my_top_level_page',
    'title'     => __( 'Sub Menu' )
  ), array(
    'alpha' => array(
      'description'   => __( 'These fields are applied straight into the class construction.' ),
      'fields'    => array(
        'one'   => array(
          'type'      => 'checkbox',
          'label'     => __( 'My Option' ),
          'default'   => true
        ),
        'two'   => array(
          'type'          => 'url',
          'label'         => __( 'An URL' ),
          'description'   => __( 'This shall match an URL format' )
        )
      )
    )
  )
);


// __________________________________________________________

// Advanced example of fields declaration
$my_top_page->apply_settings( array(
  'first'    => array(
    'title'         => __( 'My first section title' ),
    'description'   => __( 'My first section description.' ),
    'fields'        => array(
      'one'   => array(
        'type'          => 'checkbox',
        'label'         => __( 'A checkbox example' ),
        'description'   => __( 'This is the checkbox description.' )
      ),
      'two'   => array(
        'type'      => 'radio',
        'label'     => __( 'A radio example' ),
        'options'   => array(
          'value_one'     => __( 'Label one' ),
          'value_two'     => __( 'Label two' ),
          'value_three'   => __( 'Label three' )
        )
      ),
      'three' => array(
        'type'      => 'select',
        'label'     => __( 'A select example' ),
        'options'   => array(
          'value_one'     => __( 'Label one' ),
          'value_two'     => __( 'Label two' ),
          'value_three'   => __( 'Label three' )
        ),
        'default'   => 'value_two'
      ),
      'four'  => array(
        'type'          => 'textarea',
        'label'         => __( 'Text Area' ),
        'description'   => __( 'Using a callback function, this will be uppercased.' ),
        'sanitize'      => 'wm_settings_examples_uppercase',
        'attributes'    => array(
          'style' => 'background-color: black; color: green; font-weight: bold;',
          'rows'  => 3
        ),
        'default'       => __( '>' )
      )
    )
  ),
  'second'  => array(
    'title'     => __( 'My second section title' ),
    'fields'    => array(
      'one'   => array(
        'type'          => 'media',
        'label'         => __( 'Media Upload' ),
        'description'   => __( 'Using the WordPress Media Uploader.' )
      ),
      'two'   => array(
        'type'          => 'email',
        'label'         => __( 'An email' ),
        'description'   => __( 'This shall match an email format' )
      ),
      'three' => array(
        'type'          => 'number',
        'label'         => __( 'A number' ),
        'description'   => __( 'This shall match an number format' ),
        'attributes'    => array(
          'min'   => -32,
          'max'   => 64,
          'step'  => 8
        )
      ),
      'four'   => array(
        'type'          => 'url',
        'label'         => __( 'An URL' ),
        'description'   => __( 'This shall match an URL format' )
      )
    )
  )
) );

// Example of sanitation callback
function wm_settings_examples_uppercase( $input ) {
    return sanitize_text_field( strtoupper( $input ) );
}
