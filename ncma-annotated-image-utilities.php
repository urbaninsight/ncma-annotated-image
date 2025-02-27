<?php

// Debug ----------------------------------------------------------------------

// Generic function for writing data to wp-content/debug.log file
function ncma_annotated_image_write_log( $log ) {
    if ( true === WP_DEBUG ) {
        if ( is_array($log) || is_object($log) ) {
            error_log( print_r($log, true) );
        } else {
            error_log( $log );
        }
    }
}


// Image Sizes ----------------------------------------------------------------

// For retrieving all image sizes in format matching ACF images
// function kkane_ncma_annotated_image_get_image_sizes($attachment_id) {
//     if (empty($attachment_id)) {
//         return null;
//     }

//     $sizes = get_intermediate_image_sizes();

//     array_push($sizes, 'full'); // original - 'full' size

//     $array = array();

//     // intermediate sizes
//     foreach($sizes as $key => $value) :
//         // $value contains the name of the intermediate size

//         $size = wp_get_attachment_image_src($attachment_id, $value);

//         $array[$value] = $size[0];  // image source URL
//         $array["{$value}-width"] = $size[1]; // width in px
//         $array["{$value}-height"] = $size[2]; // height in px
//     endforeach;

//     return $array;
// }

// For retrieving only a specific image size in format matching ACF images
function kkane_ncma_annotated_image_get_specific_image_size($attachment_id, $size_key) {
    if (empty($attachment_id)) {
        return null;
    }

    $size = wp_get_attachment_image_src($attachment_id, $size_key);
    if (empty($size)) {
        return null;
    }

    $array = array();
    $array[$size_key] = $size[0];  // image source URL
    $array["{$size_key}-width"] = $size[1]; // width in px
    $array["{$size_key}-height"] = $size[2]; // height in px

    return $array;
}


// WP_Query  ------------------------------------------------------------------


// WP query used for creating the select options in tablenav top
// One option for each 'ncma-annotated-image' post title
// Stores the prompts with each
function ncma_annotated_images_wp_query() {
    $WP_Query_data = array();

    $args = array(
        'numberposts' => -1, // all
        'orderby' => 'title',
        'order' => 'ASC',
        'post_type' => 'ncma-annotated-image',
    );

    $posts = get_posts($args);

    $the_query = new WP_Query($args);

    if ($the_query->have_posts()) {

        while ($the_query->have_posts()) {
            $the_query->the_post();
            $id = get_the_ID();
            $title = get_the_title();
            $prompts = array(
                0 => get_field( 'ncma_annotated_image_en_prompt_1' ),
                1 => get_field( 'ncma_annotated_image_en_prompt_2' ),
                2 => get_field( 'ncma_annotated_image_en_prompt_3' ),
            );
            $WP_Query_data[] = array(
                'id' => $id,
                'title' => $title,
                'prompts' => $prompts
            );
        }
    }

    return $WP_Query_data;
}