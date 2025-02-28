<?php


/** 
 * Get Single Annotated Image post by ID
 **/
function ui_ncma_annotated_image_data(WP_REST_Request $request)
{

    $id = $request->get_param('id');

    // Return an error if ID is not provided
    if (empty($id)) {
        return new WP_Error('no_id', 'No ID provided', array('status' => 400));
    }

    // Fetch the post
    $post = get_post($id);

    if (! $post) {
        return new WP_Error('not_found', 'Post not found', array('status' => 404));
    }

    // Get ACF Fields
    if (function_exists('get_fields')) {
        $acf_fields = get_fields($id);

        // Convert image fields from IDs to URLs for all sizes
        array_walk_recursive($acf_fields, function (&$value, $key) {
            if (is_numeric($value) && get_post_type($value) === 'attachment') {
                $image_data = wp_get_attachment_metadata($value);
                $image_url = wp_get_attachment_url($value);

                if ($image_data && $image_url) {
                    $sizes = array();
                    foreach ($image_data['sizes'] as $size => $size_data) {
                        $sizes[$size] = wp_get_attachment_image_url($value, $size);
                    }

                    // Include full-size image
                    $sizes['full'] = $image_url;

                    $value = $sizes;
                }
            }
        });
    } else {
        $acf_fields = array();
    }
    // Structure the response
    $data = array(
        'id'       => $id,
        'title'    => get_the_title($id),
        'content'  => apply_filters('the_content', $post->post_content),
        'acf'      => $acf_fields,
    );
    return rest_ensure_response($data);
}

add_action('rest_api_init', function () {
    register_rest_route('ncma/v1', '/ncma-annotated-image/(?P<id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'ui_ncma_annotated_image_data',
        'args'     => array(
            'id' => array(
                'required'          => true,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
});



/** 
 * Get all published Single Annotated Image posts
 **/
function ui_ncma_annotated_image_get_all( WP_REST_Request $request ) {
    $query = new WP_Query(array(
        'post_type'      => 'ncma-annotated-image',
        'post_status'    => 'publish',
        'posts_per_page' => -1, // Get all published posts
    ));

    $posts = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $request->set_param('id', $post_id); // Set ID for reuse in single post function
            $posts[] = ui_ncma_annotated_image_data($request)->get_data();
        }
        wp_reset_postdata();
    }

    return rest_ensure_response($posts);
}
add_action('rest_api_init', function() {
    register_rest_route('ncma/v1', 'ncma-annotated-image', array(
        'methods' => 'GET',
        'callback' => 'ui_ncma_annotated_image_get_all',
    ));
});