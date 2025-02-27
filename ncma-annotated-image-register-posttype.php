<?php

// Custom Post Type -------------------------------------------------------------------------------------------
// Following https://kinsta.com/blog/wordpress-custom-post-types/

function ncma_annotated_image_register_post_type()
{

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
        'rewrite'   => array('slug' => 'annotated-image'),
        'menu_icon' => 'dashicons-images-alt2',
        'menu_position' => 30, // for ordering the wp-admin UI menu https://wpbeaches.com/moving-custom-post-types-higher-admin-menu-wordpress-dashboard/
        'show_in_rest' => true,
    );

    register_post_type('ncma-annotated-image', $args);
}

add_action('init', 'ncma_annotated_image_register_post_type');


// Remove row actions from /wp-admin/edit.php -----------------------------------------------------------------

function kkane_ncma_annotated_image_row_actions($actions, $post)
{
    if ('ncma-annotated-image' === $post->post_type) {
        unset($actions['inline hide-if-no-js']); // Removes the "Quick Edit" action.
        unset($actions['view']); // Removes the "View" action.
    }
    return $actions;
}
add_filter('post_row_actions', 'kkane_ncma_annotated_image_row_actions', 10, 2);


// Edit form top configuration -----------------------------------------------------------------------------------

function ncma_annotated_image_display_hello($post)
{
    if ($post->post_type != 'ncma-annotated-image') return;
    echo __('Currently, the title below is for naming the label on the previous page only. It does not get published anywhere else.');
}
add_action('edit_form_top', 'ncma_annotated_image_display_hello');


// Register ACF field group + fields for ncma-map-location post type. ----------------------------------------
// https://www.advancedcustomfields.com/resources/register-fields-via-php/
//  
// All 'key' values must be globally unique!

if (function_exists('acf_add_local_field_group')):

    // Set post_id for use in field instructions
    // Must set to empty string for when this code runs without any query params, such as on API GET requests
    if (isset($_GET["post"])) {
        $post_id = $_GET["post"];
    } else {
        $post_id = "";
    }

    /* Used to apply field groups below to the ncma-annotated-image post type */
    $location = array(
        array(
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'ncma-annotated-image',
            ),
        ),
    );
    acf_add_local_field_group(array(
        'key' => 'annotated-image-text-info',
        'title' => 'Annotated Image - Image Info',
        'menu_order' => 0,
        'fields' => array(
            /* Title */
            array(
                'key' => 'field_ncma_annotated_image_title',
                'label' => 'Title',
                'name' => 'ncma_annotated_image_title',
                'type' => 'text',
                'instructions' => '',
                'required' => 1,
                'conditional_logic' => 0,
            ),

            /* Tab Group Start for Top-Level Description */
            array(
                'key' => 'field_ncma_annotated_image_en_tab',
                'label' => 'English',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_ncma_annotated_image_en_description',
                'label' => 'Description (English)',
                'name' => 'ncma_annotated_image_en_description',
                'type' => 'textarea',
                'required' => 1,
            ),
            array(
                'key' => 'field_ncma_annotated_image_es_tab',
                'label' => 'Spanish',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_ncma_annotated_image_es_description',
                'label' => 'Description (Spanish)',
                'name' => 'ncma_annotated_image_es_description',
                'type' => 'textarea',
                'required' => 1,
            ),
            array(
                'key' => 'field_ncma_annotated_image_info_text_tab_endpoint',
                'type' => 'tab',
                'endpoint' => true,
            )
            /* Tab Group End */

            /* Additional fields can be placed here after the tab groups */
        ),
        'location' => $location
    ));

    acf_add_local_field_group(array(
        'key' => 'annotated-image-annotations',
        'title' => 'Annotated Image - Image & Annotations',
        'menu_order' => 1,
        'fields' => array(
            /* Image */
            array(
                'key' => 'field_ncma_annotated_image',
                'label' => 'Image',
                'name' => 'ncma_annotated_image',
                'type' => 'image',
                'return_format' => 'id',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            /* Image Annotations Repeater */
            array(
                'key' => 'field_ncma_annotated_image_annotations',
                'label' => 'Image Annotations',
                'name' => 'ncma_annotated_image_annotations',
                'type' => 'repeater',
                'layout' => 'block',
                'button_label' => 'Add Annotation',
                'sub_fields' => array(
                    array(
                        'key' => 'field_ncma_annotation_coordinates',
                        'label' => 'Coordinates',
                        'name' => 'ncma_annotation_coordinates',
                        'type' => 'text',
                    ),
                    /* Tab Group Start Inside Repeater */
                    array(
                        'key' => 'field_ncma_annotation_en_tab',
                        'label' => 'English',
                        'name' => '',
                        'type' => 'tab',
                        'placement' => 'top',
                    ),
                    array(
                        'key' => 'field_ncma_annotation_en_title',
                        'label' => 'Title (English)',
                        'name' => 'ncma_annotation_en_title',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_ncma_annotation_en_description',
                        'label' => 'Description (English)',
                        'name' => 'ncma_annotation_en_description',
                        'type' => 'wysiwyg',
                    ),
                    array(
                        'key' => 'field_ncma_annotation_es_tab',
                        'label' => 'Spanish',
                        'name' => '',
                        'type' => 'tab',
                        'placement' => 'top',
                    ),
                    array(
                        'key' => 'field_ncma_annotation_es_title',
                        'label' => 'Title (Spanish)',
                        'name' => 'ncma_annotation_es_title',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_ncma_annotation_es_description',
                        'label' => 'Description (Spanish)',
                        'name' => 'ncma_annotation_es_description',
                        'type' => 'wysiwyg',
                    ),
                    array(
                        'key' => 'field_ncma_annotation_text_tab_endpoint',
                        'type' => 'tab',
                        'endpoint' => true
                    ),

                    /* Tab Group End Inside Repeater */
                    array(
                        'key' => 'field_ncma_annotation_related_image',
                        'label' => 'Related Image',
                        'name' => 'ncma_annotation_related_image',
                        'type' => 'image',
                        'return_format' => 'id',
                        'preview_size' => 'medium',
                        'library' => 'all',
                    ),
                    array(
                        'key' => 'field_ncma_annotation_related_caption',
                        'label' => 'Related Image Caption',
                        'name' => 'ncma_annotation_related_caption',
                        'type' => 'text',
                    ),
                ),
            ),
        ),
        'location' => $location
    ));


endif;


// Set the featured image of a post ---------------------------------------------------------------------------
// acf/update_value/name={$field_name} - filter for a specific field based on it's name
// So, this happens if the qr_code_image field exists for any post type.

function kkane_ncma_annotated_image_acf_set_featured_image($value, $post_id, $field)
{

    if ($value != '') {
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

function ncma_annotated_image_post_updated_message($messages)
{

    $post             = get_post();
    $post_type        = get_post_type($post);
    $post_type_object = get_post_type_object($post_type);

    $messages['ncma-annotated-image'] = array(
        0  => '', // Unused. Messages start at index 1.
        1  => __('Annotated Image updated.'),
        2  => __('Custom field updated.'),
        3  => __('Custom field deleted.'),
        4  => __('Annotated Image updated.'),
        /* translators: %s: date and time of the revision */
        5  => isset($_GET['revision']) ? sprintf(__('My Post Type restored to revision from %s'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
        6  => __('Annotated Image published.'),
        7  => __('Annotated Image saved.'),
        8  => __('Annotated Image submitted.'),
        9  => sprintf(
            __('Annotated Image scheduled for: <strong>%1$s</strong>.'),
            // translators: Publish box date format, see http://php.net/date
            date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date))
        ),
        10 => __('Annotated Image draft updated.')
    );

    //you can also access items this way
    // $messages['post'][1] = "I just totally changed the Updated messages for standards posts";

    //return the new messaging 
    return $messages;
}
add_filter('post_updated_messages', 'ncma_annotated_image_post_updated_message');
