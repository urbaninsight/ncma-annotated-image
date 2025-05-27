<?php
//require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Function hooked to run after acf/save_post has saved acf data. 
 * This function will generate a IIIF Manifest JSON file for the Annotated Image post.
 * This file will be saved to the uploads directory in a folder named IIIF/ncma-annotated-image/{post_id}.json
 * @param mixed $post_id
 * @return void
 */
function save_ncma_annotated_image_iiif_manifest_json($post_id) {
    // Get the post object
    $post = get_post($post_id);
    // Ensure we're only working with the correct post type
    if ($post->post_type !== 'ncma-annotated-image') {
        return;
    }
    
    // Ensure the post is published
    if ($post->post_status !== 'publish') {
        return;
    }
    
    // Call the function to get the JSON data
    $json_data = generateIIIFManifest($post_id);
    //error_log(json_encode($json_data));
    if (empty($json_data)) {
        return;
    }
    
    // Convert the data to JSON format
    $json_content = json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // Define the file path
    $upload_dir = wp_upload_dir();
    $json_dir = trailingslashit($upload_dir['basedir']) . 'IIIF/ncma-annotated-image/';
    $json_file = $json_dir . $post_id . '.json';
    
    // Ensure the directory exists
    if (!file_exists($json_dir)) {
        wp_mkdir_p($json_dir);
    }
    
    // Write the JSON data to the file
    file_put_contents($json_file, $json_content);
}
add_action('acf/save_post', 'save_ncma_annotated_image_iiif_manifest_json', 20, 1);

/**
 * This function takes a full set of ACF fields, finds the annotations list, and transforms it to
 * match the expected API response format. Most importantly we are replacing image IDs with urls and also
 * replacing multi-language fields with a single field broken out by language code.
 * @param mixed $acf_fields
 * @return array{annotation_coordinates: mixed, annotation_description: array, annotation_related_caption: array, annotation_related_image: array|null, annotation_title: array[]}
 */
function transformAnnotationsForAPIResponse($acf_fields) {
    $annotations = $acf_fields['ncma_annotated_image_annotations'] ?? [];
    
    return array_map(function ($annotation) {
        return [
            'annotation_coordinates' => $annotation['ncma_annotation_coordinates'] ?? null,
            'annotation_title' => [
                'en' => $annotation['ncma_annotation_en_title'] ?? '',
                'es' => $annotation['ncma_annotation_es_title'] ?? ''
            ],
            'annotation_description' => [
                'en' => $annotation['ncma_annotation_en_description'] ?? '',
                'es' => $annotation['ncma_annotation_es_description'] ?? ''
            ],
            'annotation_related_image' => ui_get_image_urls_from_id($annotation['ncma_annotation_related_image'] ?? null),
            'annotation_related_caption' => [
                'en' => $annotation['ncma_annotation_related_caption_en'] ?? '',
                'es' => $annotation['ncma_annotation_related_caption_es'] ?? ''
            ]
        ];
    }, $annotations);
}

/**
 * Helper function that converts a media ID to an array of image URLs for various sizes.
 * @param mixed $image_id
 * @return array|null
 */
function ui_get_image_urls_from_id($image_id) {
    if (!$image_id) {
        return null;
    }
    
    $image_sizes = ['thumbnail', 'medium', 'medium_large', 'large', 'full'];
    $image_urls = [];
    
    foreach ($image_sizes as $size) {
        $image_url = wp_get_attachment_image_url($image_id, $size);
        if ($image_url) {
            $image_urls[$size] = $image_url;
        }
    }
    
    return $image_urls;
}

/**
 * Generates a IIIF Manifest JSON object for a given post ID.
 * Only works for Annotated Image posts.
 * @param mixed $post_id
 */
function generateIIIFManifest($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error('not_found', 'Post not found', array('status' => 404));
    }

    $acf_fields = function_exists('get_fields') ? get_fields($post_id) : [];

    // Use a base URI for clean identifiers
    $base_uri = get_site_url() . "/wp-json/ncma/v1/ncma-annotated-image/{$post_id}/IIIF";

    // Retrieve image ID from ACF
    $image_id = $acf_fields['ncma_annotated_image'] ?? null;
    $image_url = $image_id ? wp_get_attachment_url($image_id) : '';

    //wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, get_attached_file($image_id)));

    // Get image metadata (width & height)
    $image_meta = $image_id ? wp_get_attachment_metadata($image_id) : [];
    // $image_info = wp_get_attachment_image_src($image_id, 'full'); // Get the full image source

    $image_width = $image_meta['width'] ?? -1;
    $image_height = $image_meta['height'] ?? -1;
    // $image_width = $image_info ? $image_info[1] : -1; // Width from the image source
    // $image_height = $image_info ? $image_info[2] : -1; // Width from the image source


    $manifest = [
        "@context" => "http://iiif.io/api/presentation/3/context.json",
        "id" => $base_uri,
        "type" => "Manifest",
        "label" => [
            "en" => [$acf_fields['ncma_annotated_image_title']],
            "es" => [$acf_fields['ncma_annotated_image_title_es'] ?? '']
        ],
        "summary" => [
            "en" => [$acf_fields['ncma_annotated_image_en_description']],
            "es" => [$acf_fields['ncma_annotated_image_es_description'] ?? '']
        ],
        "items" => []
    ];

    // Construct the Canvas
    $canvas = [
        "id" => $base_uri . "/canvas",
        "type" => "Canvas",
        "width" => $image_width,
        "height" => $image_height,
        "items" => [
            [
                "id" => $base_uri . "/canvas/painting_page",
                "type" => "AnnotationPage",
                "items" => [
                    [
                        "id" => $base_uri . "/canvas/painting",
                        "type" => "Annotation",
                        "motivation" => "painting",
                        "body" => [
                            "id" => $image_url,
                            "type" => "Image",
                            "format" => "image/jpeg",
                            "width" => $image_width,
                            "height" => $image_height,
                        ], 
                        "target" => $base_uri . "/canvas"
                    ]
                ]
            ]
        ],
        "annotations" => transformAnnotationsForIIIF($acf_fields, $base_uri, $image_width, $image_height)
    ];

    $manifest["items"][] = $canvas;

    return $manifest;
}

/**
 * Converts a percentage string to a pixel value based on the given dimension.
 *
 * @param string $percent The percentage string (e.g., "45%").
 * @param int $dimension The total dimension (width or height) in pixels.
 * @return int The computed pixel value casted as
 */
function convertPercentToPixel($percent, $dimension) {
    $percent_value = floatval(str_replace('%', '', $percent));
    return round(($percent_value / 100) * $dimension);
}

/**
 * Parses a coordinate string like '24.123%,75.001%' into pixel values.
 *
 * @param string $coordinate_string The coordinate string from ACF.
 * @param int    $canvas_width      The width of the canvas in pixels.
 * @param int    $canvas_height     The height of the canvas in pixels.
 * @return array Associative array with 'x' and 'y' pixel values.
 */
function parseCoordinates($coordinate_string, $canvas_width, $canvas_height) {
    $coordinates = explode(',', $coordinate_string);
    $x_percent = trim($coordinates[0] ?? '0%');
    $y_percent = trim($coordinates[1] ?? '0%');

    return [
        'x' => convertPercentToPixel($x_percent, $canvas_width),
        'y' => convertPercentToPixel($y_percent, $canvas_height)
    ];
}

function ui_ncma_add_video_attributes($iframe) {
 // Use preg_match to find iframe src.
preg_match('/src="(.+?)"/', $iframe, $matches);
$src = $matches[1];

// Add extra parameters to src and replace HTML.
$params = array(
    'controls'  => 0,
    'autoplay'  => 1,
    'muted'   => 1,
    'loop'      => 1,
    'background' => 1,
);
$new_src = add_query_arg($params, $src);
$iframe = str_replace($src, $new_src, $iframe);

// Add extra attributes to iframe HTML.
$attributes = 'frameborder="0"';
$iframe = str_replace('></iframe>', ' ' . $attributes . '></iframe>', $iframe);

// Display customized HTML.
return $iframe;
}


/**
 * Transforms annotations stored in ACF fields into IIIF-compliant annotation objects.
 *
 * @param array  $acf_fields    The ACF fields from the post.
 * @param string $base_uri      The base URI for generating unique identifiers.
 * @param int    $canvas_width  The width of the canvas (image).
 * @param int    $canvas_height The height of the canvas (image).
 * @return array An array of annotation objects.
 */
function transformAnnotationsForIIIF($acf_fields, $base_uri, $canvas_width, $canvas_height) {
    $annotations = $acf_fields['ncma_annotated_image_annotations'] ?? [];

    $annotation_items = array_map(function ($annotation) use ($base_uri, $canvas_width, $canvas_height) {
        $coordinates = parseCoordinates($annotation['ncma_annotation_coordinates'] ?? '0%,0%', $canvas_width, $canvas_height);
        $x = $coordinates['x'];
        $y = $coordinates['y'];

        $annotation_item = [
            "id" => "{$base_uri}/annotation/{$x}x{$y}",
            "type" => "Annotation",
            "motivation" => "tagging",
            // "label" => [
            //     "en" => [$annotation['ncma_annotation_en_title']],
            //     "es" => [$annotation['ncma_annotation_es_title'] ?? '']
            // ],
            "body" => [],
        ];

        if (!empty($annotation['ncma_annotation_en_description'])) {
            $annotation_item['body'][] = [
                "type" => "TextualBody",
                "label" => $annotation['ncma_annotation_en_title'],
                "value" => $annotation['ncma_annotation_en_description'],
                "format" => "text/html",
                "language" => "en"
            ];
            $annotation_item['body'][] = [
                "type" => "TextualBody",
                "label" => $annotation['ncma_annotation_es_title'],
                "value" => $annotation['ncma_annotation_es_description'],
                "format" => "text/html",
                "language" => "es"
            ];
            if(!empty($annotation['ncma_annotation_related_image'])){
                $annotation_item['body'][] = [
            
                    "type" => "Image",
                    "label" => [
                        "en" => $annotation['ncma_annotation_related_caption_en'],
                        "es" => $annotation['ncma_annotation_related_caption_es']
                    ],
                    "id" => wp_get_attachment_url($annotation['ncma_annotation_related_image']),
                    "format" => "image/jpeg"
            ];
            }
        }
        // if (!empty($annotation['ncma_annotation_es_description'])) {
        //     $annotation_item['body'][] = [
        //         "type" => "TextualBody",
        //         "value" => $annotation['ncma_annotation_es_description'],
        //         "format" => "text/plain",
        //         "language" => "es"
        //     ];
        // }

        if (!empty($annotation['ncma_annotation_related_image'])) {
            $attachment_id = $annotation['ncma_annotation_related_image'];
            $image_url = wp_get_attachment_url($attachment_id);
            $image_meta = wp_get_attachment_metadata($attachment_id);
            $img_width = $image_meta['width'] ?? 0;
            $img_height = $image_meta['height'] ?? 0;
            if ($image_url) {
                // $annotation_item['body'][] = [
                //     "type" => "Image",
                //     "id" => $image_url,
                //     "format" => "image/jpeg",
                //     "width"  => $img_width,
                //     "height" => $img_height
                // ];
            }
        }

        // if (!empty($annotation['ncma_annotation_related_caption_en'])) {
        //     $annotation_item['body'] = array(
        //         "type" => "TextualBody",
        //         "value" => $annotation['ncma_annotation_related_caption_en'],
        //         "format" => "text/plain",
        //         "language" => "en"
        //     );
        // }
        // if (!empty($annotation['ncma_annotation_related_caption_es'])) {
        //     $annotation_item['body'][] = [
        //         "type" => "TextualBody",
        //         "value" => $annotation['ncma_annotation_related_caption_es'],
        //         "format" => "text/plain",
        //         "language" => "es"
        //     ];
        // }

        $annotation_item["target"] = [
            "type" => "SpecificResource",
            "source" => $base_uri . "/canvas",
            "selector" => [
                "type" => "PointSelector",
                "x" => $x,
                "y" => $y
            ]
        ];

        return $annotation_item;
    }, $annotations);

    // Wrap the annotations inside an AnnotationPage
    return [
        [
            "id" => "{$base_uri}/canvas/annotation_page",
            "type" => "AnnotationPage",
            "items" => $annotation_items
        ]
    ];
}