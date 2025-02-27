<?php

// Custom Post Type -------------------------------------------------------------------------------------------
// Following https://kinsta.com/blog/wordpress-custom-post-types/

function ncma_annotated_image_register_post_type() {

    $labels = array(
        'name' => 'Annotated Images',
        'singular_name' => 'Annotated Image',
        'add_new' => 'New Annotated Image',
        'add_new_item' => 'Add New Annotated Image',
        'edit_item' => 'Edit Annotated Image',
        'new_item' => 'New Annotated Image',
        'view_item' => 'View Annotated Images',
        'search_items' => 'Search Annotated Images',
        'not_found' =>  'No Annotated Images Found',
        'not_found_in_trash' => 'No Annotated Images found in Trash',
    );

    $args = array(
    'labels' => $labels,
    'has_archive' => false,
    'public' => true,
    'hierarchical' => false,
    'supports' => array(
        'title',
        'custom-fields',
    ),
    'rewrite'   => array( 'slug' => 'annotated-image' ),
    'menu_icon' => 'dashicons-images-alt2',
    'menu_position' => 30, // for ordering the wp-admin UI menu https://wpbeaches.com/moving-custom-post-types-higher-admin-menu-wordpress-dashboard/
    'show_in_rest' => true,
    );

    register_post_type( 'ncma-annotated-image', $args );

}

add_action( 'init', 'ncma_annotated_image_register_post_type' );


// Remove row actions from /wp-admin/edit.php -----------------------------------------------------------------

function kkane_ncma_annotated_image_row_actions( $actions, $post ) {
    if ( 'ncma-annotated-image' === $post->post_type ) {
        unset( $actions['inline hide-if-no-js'] ); // Removes the "Quick Edit" action.
        unset( $actions['view'] ); // Removes the "View" action.
    }
    return $actions;
}
add_filter( 'post_row_actions', 'kkane_ncma_annotated_image_row_actions', 10, 2 );


// Edit form top configuration -----------------------------------------------------------------------------------

function ncma_annotated_image_display_hello( $post ) {
    if ($post->post_type != 'ncma-annotated-image') return;
    echo __( 'Currently, the title below is for naming the label on the previous page only. It does not get published anywhere else.' );
}
add_action( 'edit_form_top', 'ncma_annotated_image_display_hello' );


// Register ACF field group + fields for ncma-map-location post type. ----------------------------------------
// https://www.advancedcustomfields.com/resources/register-fields-via-php/
//  
// All 'key' values must be globally unique!

if( function_exists('acf_add_local_field_group') ):

    // Set post_id for use in field instructions
    // Must set to empty string for when this code runs without any query params, such as on API GET requests
    if (isset($_GET["post"])) {
        $post_id = $_GET["post"];
    } else {
        $post_id = "";
    }

    /* Used to apply field groups below to the ncma-annotated-image post type */
    $location = array (
        array (
            array (
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'ncma-annotated-image',
            ),
        ),
    );

    /* Group for fields that do not change with language */
    acf_add_local_field_group(array(
        'key' => 'annotated-image-universal',
        'title' => 'Annotated Image - Text - English only',
        'fields' => array (
            /* English tab */
            array(
                'key' => 'field_ncma_annotated_image_en_text_tab',
                'label' => 'English',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            /* English fields */
            array (
                'key' => 'field_ncma_annotated_image_en_heading',
                'label' => 'Heading',
                'name' => 'ncma_annotated_image_en_heading',
                'type' => 'text',
                'instructions' => 'This heading appears above the prompts on the prompt selection menu.',
                'required' => 1,
                'conditional_logic' => 0,
            ),
            array (
                'key' => 'field_ncma_annotated_image_en_prompt_1',
                'label' => 'Prompt 1',
                'name' => 'ncma_annotated_image_en_prompt_1',
                'type' => 'text',
                'instructions' => 'An evocative prompt related to exhibit themes for visitors to respond to.',
                'required' => 1,
                'conditional_logic' => 0,
            ),
            array (
                'key' => 'field_ncma_annotated_image_en_prompt_2',
                'label' => 'Prompt 2',
                'name' => 'ncma_annotated_image_en_prompt_2',
                'type' => 'text',
                'instructions' => 'An evocative prompt related to exhibit themes for visitors to respond to.',
                'required' => 0,
                'conditional_logic' => 0,
            ),
            array (
                'key' => 'field_ncma_annotated_image_en_prompt_3',
                'label' => 'Prompt 3',
                'name' => 'ncma_annotated_image_en_prompt_3',
                'type' => 'text',
                'instructions' => 'An evocative prompt related to exhibit themes for visitors to respond to.',
                'required' => 0,
                'conditional_logic' => 0,
            ),
            /* Spanish tab */
            // array(
            //     'key' => 'field_ncma_annotated_image_es_text_tab',
            //     'label' => 'Spanish',
            //     'name' => '',
            //     'type' => 'tab',
            //     'instructions' => '',
            //     'required' => 0,
            //     'conditional_logic' => 0,
            //     'wrapper' => array(
            //         'width' => '',
            //         'class' => '',
            //         'id' => '',
            //     ),
            //     'placement' => 'top',
            //     'endpoint' => 0,
            // ),
            /*Spanish fields*/
            // array (
            //     'key' => 'field_ncma_annotated_image_es_prompt_1',
            //     'label' => 'Prompt 1',
            //     'name' => 'ncma_annotated_image_es_prompt_1',
            //     'type' => 'text',
            //     'instructions' => 'An evocative prompt related to exhibit themes for visitors to respond to.',
            //     'required' => 0,
            //     'conditional_logic' => 0,
            // ),
            // array (
            //     'key' => 'field_ncma_annotated_image_es_prompt_2',
            //     'label' => 'Prompt 2',
            //     'name' => 'ncma_annotated_image_es_prompt_2',
            //     'type' => 'text',
            //     'instructions' => 'An evocative prompt related to exhibit themes for visitors to respond to.',
            //     'required' => 0,
            //     'conditional_logic' => 0,
            // ),
            // array (
            //     'key' => 'field_ncma_annotated_image_es_prompt_3',
            //     'label' => 'Prompt 3',
            //     'name' => 'ncma_annotated_image_es_prompt_3',
            //     'type' => 'text',
            //     'instructions' => 'An evocative prompt related to exhibit themes for visitors to respond to.',
            //     'required' => 0,
            //     'conditional_logic' => 0,
            // ),
        ),
        'location' => $location,
    ));

     /* Field group for QR code and help text content - English, Spanish */
     acf_add_local_field_group(array(
        'key' => 'annotated-image-qr-code',
        'title' => 'Annotated Image - QR Code - English only',
        'fields' => array (
            /* QR Code tab */
            array(
                'key' => 'field_ncma_annotated_image_qr_code_tab',
                'label' => 'QR Code',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            /* QR Code image field */
            array (
                'key' => 'field_ncma_annotated_image_qr_code',
                'label' => 'QR Code',
                'name' => 'ncma_annotated_image_qr_code',
                'type' => 'relationship',
                'instructions' => "A QR code for visitors to submit responses from their personal device. The QR code is published below the prompt selection menu. <br />This Annotated Image will be published at <span style='color:indianred;'>https://ncma-kiosks.pages.dev/response/{$post_id}</span>. Make sure your QR code links to this address.",
                'required' => 0,
                'min' => 0,
                'max' => 1,
                'post_type' => 'ncma-qr-code',
                'filters' => array('search'),
                'elements' => array('featured_image'),
                'return_format' => 'id',
                'conditional_logic' => 0,
            ),
            /* English tab */
            array(
                'key' => 'field_ncma_annotated_image_en_qr_code_tab',
                'label' => 'English',
                'name' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'placement' => 'top',
                'endpoint' => 0,
            ),
            /* English fields */
            array (
                'key' => 'field_ncma_annotated_image_en_qr_code_text',
                'label' => 'QR Code Instruction (English)',
                'name' => 'ncma_annotated_image_en_qr_code_text',
                'type' => 'text',
                'instructions' => 'Text placed next to the QR code instructing users how to use it.',
                'default_value' => 'Interested in sharing from your own device? <br />Scan the QR code to submit and answer from your phone.',
                'required' => 0,
                'conditional_logic' => 0,
            ),
            // /* Spanish tab */
            // array(
            //     'key' => 'field_ncma_annotated_image_es_qr_code_tab',
            //     'label' => 'Spanish',
            //     'name' => '',
            //     'type' => 'tab',
            //     'instructions' => '',
            //     'required' => 0,
            //     'conditional_logic' => 0,
            //     'wrapper' => array(
            //         'width' => '',
            //         'class' => '',
            //         'id' => '',
            //     ),
            //     'placement' => 'top',
            //     'endpoint' => 0,
            // ),
            // /* Spanish fields */
            // array (
            //     'key' => 'field_ncma_annotated_image_es_qr_code_text',
            //     'label' => 'Help text (Español)',
            //     'name' => 'ncma_annotated_image_en_qr_code_text',
            //     'type' => 'textarea',
            //     'instructions' => 'If QR code is present, refer to the QR code. Otherwise, use generic help text.',
            //     'required' => 1,
            //     'conditional_logic' => 0,
            // ),
        ),
        'location' => $location,
    ));

endif;


// Set the featured image of a post ---------------------------------------------------------------------------
// acf/update_value/name={$field_name} - filter for a specific field based on it's name
// So, this happens if the qr_code_image field exists for any post type.

function kkane_ncma_annotated_image_acf_set_featured_image( $value, $post_id, $field  ){
    
    if($value != ''){
	    //Add the value which is the image ID to the _thumbnail_id meta data for the current post
	    update_post_meta($post_id, '_thumbnail_id', $value);
    }
 
    return $value;
}
add_filter('acf/update_value/name=qr_code_image', 'kkane_ncma_annotated_image_acf_set_featured_image', 10, 3);


// Modify the default WordPress post updated messages that are displayed --------------------------------------
// when making changes to a post of the 'ncma-map-artwork' type.
// https://ryanwelcher.com/2014/10/change-wordpress-post-updated-messages/
// https://developer.wordpress.org/reference/hooks/post_updated_messages/

function ncma_annotated_image_post_updated_message($messages) {
    
	$post             = get_post();
	$post_type        = get_post_type( $post );
	$post_type_object = get_post_type_object( $post_type );
	
	$messages['ncma-annotated-image'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => __( 'Annotated Image updated.' ),
		2  => __( 'Custom field updated.' ),
		3  => __( 'Custom field deleted.'),
		4  => __( 'Annotated Image updated.' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'My Post Type restored to revision from %s' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => __( 'Annotated Image published.' ),
		7  => __( 'Annotated Image saved.' ),
		8  => __( 'Annotated Image submitted.' ),
		9  => sprintf(
			__( 'Annotated Image scheduled for: <strong>%1$s</strong>.' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
		),
		10 => __( 'Annotated Image draft updated.' )
	);

    //you can also access items this way
    // $messages['post'][1] = "I just totally changed the Updated messages for standards posts";

    //return the new messaging 
	return $messages;
}
add_filter( 'post_updated_messages', 'ncma_annotated_image_post_updated_message' );