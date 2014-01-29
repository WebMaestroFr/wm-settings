<?php

// __________________________________________________________

// A really minimal declaration, to show the basic defaults :

$fields = array(
    'my_setting_id' => array(
        'title'     => __( 'My Section' ),
        'fields'    => array(
            'my_option_name'    => array(
                'type'          => 'checkbox',
                'label'         => __( 'My Option' ),
                'description'   => __( 'This is the checkbox description text.' )
            ),
            'other_option'      => array(
                'label'     => __( 'My Other Option' ),
                'default'   => true
            )
        )
    )
);

$my_page = new WM_Settings( 'my_page_id', __( 'My Page' ), array(
    'parent'    => 'themes.php',
    'title'     => __( 'My Page Menu' )
), $fields );

// Visit this example at /wp-admin/themes.php?page=my_page_id

// Now to access "My Option" value :
// $value = wm_get_option( 'my_setting_id', 'my_option_name' );
// Or :
// $setting = get_option( 'my_setting_id' ); $value = $setting['my_option_name'];


// __________________________________________________________

// A top level menu :

$page_top = new WM_Settings( 'my_top_level_page', __( 'My Top Level Page' ), array(
    // http://codex.wordpress.org/Function_Reference/add_menu_page#Parameters
    'parent'        => false,
    'title'         => __( 'Top Level Menu' ),
    'capability'    => 'manage_options',
    'icon_url'      => 'dashicons-admin-generic',
    'position'      => '63.3'
) );

// And its sub-page, with fields pre-declared :

$page_sub = new WM_Settings( 'my_sub_page', __( 'My Sub Page' ), array(
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
) );


// __________________________________________________________

// Advanced fields declaration

$page_top->apply_settings( array(
    'first' => array(
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
                'description'   => __( 'Using a callback function, "Hello" will be added to the beginning of this text when settings will be saved.' ),
                'callback'      => 'wm_settings_examples_add_hello',
                'attributes'    => array(
                    'style' => 'background-color: black; color: green; font-weight: bold;',
                    'rows'  => 10
                ),
                'default'       => __( '>' )
            )
        )
    ),
    'second'    => array(
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
                'label'         => __( 'An number' ),
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

function wm_settings_examples_add_hello( $input ) {
    return "> Hello !\n" . $input;
}
