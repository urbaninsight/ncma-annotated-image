<?php

// Custom Post Type Registration for Annotated Images -------------------------------------------------------

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


// Modified from ncma-digital-label -----------------------------------------------------------------

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


// Register ACF field group for post type. ----------------------------------------
// https://www.advancedcustomfields.com/resources/register-fields-via-php/

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
                'label' => 'Title (English)',
                'name' => 'ncma_annotated_image_title',
                'type' => 'text',
                'instructions' => '',
                'required' => 1,
                'conditional_logic' => 0,
            ),
            array(
                'key' => 'field_ncma_annotated_image_title_es',
                'label' => 'Title (Spanish)',
                'name' => 'ncma_annotated_image_title_es',
                'type' => 'text',
                'instructions' => '',
                'required' => 1,
                'conditional_logic' => 0,
            ),
            array(
                'key' => 'field_ncma_annotated_image_description_image',
                'label' => 'Description Image',
                'name' => 'ncma_annotated_image_description_image', // This is the field name used in the database
                'type' => 'image',
                'return_format' => 'id', // or 'url' or 'id'
                'preview_size' => 'medium',
                'library' => 'all',
                'mime_types' => 'jpg,jpeg,png,gif', // Restrict to images
            ),
            array(
                'key' => 'field_ncma_annotated_image_description_video',
                'label' => 'Description Video',
                'name' => 'ncma_annotated_image_description_video',
                'type' => 'oembed', // Allows embedding videos
                'instructions' => 'Enter a video URL (YouTube, Vimeo, etc.)',
                'required' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
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
            ),
            array(
                'key' => 'field_ncma_annotation_color',
                'label' => 'Annotation Color',
                'name' => 'ncma_annotation_color',
                'type' => 'color_picker',
                'default_value' => '#ff0000', // Default color (Red)
            ),
            array(
                'key' => 'field_ncma_annotation_highlight_color',
                'label' => 'Annotation Highlight Color',
                'name' => 'ncma_annotation_highlight_color',
                'type' => 'color_picker',
                'default_value' => '#F9CF48', // Default color (Yellow)
            ),
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
                        'key' => 'field_ncma_annotation_accordion_ui',
                        'label' => 'Annotation',
                        'type' => 'accordion',
                        'open' => 0,
                        'multi_expand' => 0,
                        'endpoint' => 0,
                    ),
            
                    array(
                        'key' => 'field_ncma_annotation_coordinates',
                        'label' => 'Annotation Coordinates',
                        'name' => 'ncma_annotation_coordinates',
                        'type' => 'image_mapping',
                        'image_field_label' => 'ncma_annotated_image',
                        'percent_based' => 1,
                        'font_size' => 14,
                    ),
            
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
                        'type' => 'textarea',
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
                        'type' => 'textarea',
                    ),
            
                    array(
                        'key' => 'field_ncma_annotation_text_tab_endpoint',
                        'type' => 'tab',
                        'endpoint' => 1,
                    ),
                    array(
                        'key' => 'field_ncma_annotation_text_tab_endpoint2',
                        'type' => 'tab',
                        'endpoint' => true,
                    ),
            
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
                        'key' => 'field_ncma_annotation_text_tab_endpoint3',
                        'type' => 'tab',
                        'endpoint' => true,
                    ),
            
                    array(
                        'key' => 'field_ncma_annotation_en_tab_related_caption',
                        'label' => 'English',
                        'name' => '',
                        'type' => 'tab',
                        'placement' => 'top',
                    ),
                    array(
                        'key' => 'field_ncma_annotation_related_caption_en',
                        'label' => 'Related Image Caption (English)',
                        'name' => 'ncma_annotation_related_caption_en',
                        'type' => 'text',
                    ),
            
                    array(
                        'key' => 'field_ncma_annotation_es_tab_caption',
                        'label' => 'Spanish',
                        'name' => '',
                        'type' => 'tab',
                        'placement' => 'top',
                    ),
                    array(
                        'key' => 'field_ncma_annotation_related_caption_es',
                        'label' => 'Related Image Caption (Spanish)',
                        'name' => 'ncma_annotation_related_caption_es',
                        'type' => 'text',
                    ),
            
                    array(
                        'key' => 'field_ncma_annotation_caption_tab_endpoint_2',
                        'type' => 'tab',
                        'endpoint' => true,
                    ),
                ),
            ),
            
        ),
        'location' => $location
    ));

    acf_add_local_field_group(array(
        'key' => 'annotated-image-iiif-meta',
        'title' => 'IIIF Meta',
        'menu_order' => 20,
        'fields' => array(
            array(
                'key' => 'field_ncma_copyright_statement',
                'label' => 'Copyright Statement',
                'name' => 'ncma_copyright_statement',
                'type' => 'text',
                'instructions' => 'Enter the copyright statement for this image',
                'required' => 0,
            ),
            array(
                'key' => 'field_ncma_creator',
                'label' => 'Creator',
                'name' => 'ncma_creator',
                'type' => 'text',
                'instructions' => 'Enter the creator of the image',
                'required' => 0,
            ),
            array(
                'key' => 'field_ncma_object_number',
                'label' => 'Object Number',
                'name' => 'ncma_object_number',
                'type' => 'text',
                'instructions' => 'Enter the object number',
                'required' => 0,
            ),
            array(
                'key' => 'field_ncma_required_statement_en_tab',
                'label' => 'English',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_ncma_required_statement_en',
                'label' => 'Required Statement (English)',
                'name' => 'ncma_required_statement_en',
                'type' => 'text',
                'instructions' => 'Enter any required statement for this image in English',
                'required' => 0,
            ),
            array(
                'key' => 'field_ncma_required_statement_es_tab',
                'label' => 'Spanish',
                'name' => '',
                'type' => 'tab',
                'placement' => 'top',
            ),
            array(
                'key' => 'field_ncma_required_statement_es',
                'label' => 'Required Statement (Spanish)',
                'name' => 'ncma_required_statement_es',
                'type' => 'text',
                'instructions' => 'Enter any required statement for this image in Spanish',
                'required' => 0,
            ),
            array(
                'key' => 'field_ncma_required_statement_tab_endpoint',
                'type' => 'tab',
                'endpoint' => true,
            ),
            array(
                'key' => 'field_ncma_rights_url',
                'label' => 'Rights URL',
                'name' => 'ncma_rights_url',
                'type' => 'url',
                'instructions' => 'Enter the URL for rights information',
                'required' => 0,
            ),
        ),
        'location' => $location
    ));


endif;


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
