<?php



/** 
 * Get Single Annotated Image post by ID
 **/
function ui_ncma_annotated_image_data_custom(WP_REST_Request $request)
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

    } else {
        $acf_fields = array();
    }
    // Structure the response
    $data = array(
        'id'       => $id,
        'title'    => $acf_fields['ncma_annotated_image_title'],
        'description' => array(
            'en' => $acf_fields['ncma_annotated_image_en_description'],
            'es' => $acf_fields['ncma_annotated_image_es_description'],),
        'image' => ui_get_image_urls_from_id($acf_fields['ncma_annotated_image'] ?? null),
        'image_annotations' => transformAnnotationsForAPIResponse($acf_fields),
    );

    return rest_ensure_response($data);
}


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
            $posts[] = ui_ncma_annotated_image_data_custom($request)->get_data();
        }
        wp_reset_postdata();
    }

    return rest_ensure_response($posts);
}

/**
 * Get IIIF Manfiest JSON for a single Annotated Image post
 * @param WP_REST_Request $request
 */
function ui_ncma_annotated_image_get_iiif_manifest(WP_REST_Request $request) {
    $id = $request->get_param('id');
    $manifest = generateIIIFManifest($id);

    if (is_wp_error($manifest)) {
        return $manifest;
    }

    return rest_ensure_response($manifest);
}

/**
 * Register REST API routes
 */

// .../wp-json/ncma/v1/ncma-annotated-image/{id}
add_action('rest_api_init', function () {
    register_rest_route('ncma/v1', '/ncma-annotated-image/(?P<id>\d+)', array(
        'methods'  => 'GET',
        'callback' => 'ui_ncma_annotated_image_data_custom',
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

// .../wp-json/ncma/v1/ncma-annotated-image
add_action('rest_api_init', function() {
    register_rest_route('ncma/v1', 'ncma-annotated-image', array(
        'methods' => 'GET',
        'callback' => 'ui_ncma_annotated_image_get_all',
    ));
});

// .../wp-json/ncma/v1/ncma-annotated-image/{id}/IIIF
add_action('rest_api_init', function() {
    register_rest_route('ncma/v1', '/ncma-annotated-image/(?P<id>\d+)/IIIF', array(
        'methods' => 'GET',
        'callback' => 'ui_ncma_annotated_image_get_iiif_manifest',
    ));
});