<?php

/**
 * This function takes a full set of ACF fields, finds the annotations list, and transforms it to
 * match the expected API response format. Most importantly we are replacing image IDs with urls and also
 * replacing multi-language fields with a single field broken out by language code.
 * @param mixed $acf_fields
 * @return array{annotation_coordinates: mixed, annotation_description: array, annotation_related_caption: array, annotation_related_image: array|null, annotation_title: array[]}
 */
function transformAnnotationsForAPIResponse($acf_fields)
{
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
 * Generates a IIIF Manifest JSON object for a given post ID.
 * Only works for Annotated Image posts.
 * @param mixed $post_id
 */
function generateIIIFManifest($post_id)
{
    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error('not_found', 'Post not found', array('status' => 404));
    }

    $acf_fields = function_exists('get_fields') ? get_fields($post_id) : [];

    // Use a base URI for clean identifiers
    $base_uri = get_site_url() . "/wp-json/ncma/v1/ncma-annotated-image/{$post_id}/IIIF";

    // Retrieve image ID from ACF
    $image_id = $acf_fields['ncma_annotated_image'] ?? null;
    $image_url = $image_id ? wp_get_attachment_url(attachment_id: $image_id) : '';

    //wp_update_attachment_metadata($image_id, wp_generate_attachment_metadata($image_id, get_attached_file($image_id))); // uncomment if we have trouble with image meta populating

    // Get image metadata (width & height)
    $image_meta = $image_id ? wp_get_attachment_metadata($image_id) : [];

    $image_width = $image_meta['width'] ?? -1;
    $image_height = $image_meta['height'] ?? -1;


    $manifest = [
        "@context" => "http://iiif.io/api/presentation/3/context.json",
        "id" => $base_uri,
        "type" => "Manifest",
        "label" => [
            "en" => [$acf_fields['ncma_annotated_image_title']],
            "es" => [$acf_fields['ncma_annotated_image_title_es'] ?? '']
        ],
        "metadata" => [ 
            [
            "label" => [
                "en" => "Title",
                "es" => "Título"
            ],
            "value" => [
                "en" => get_bloginfo('name')
                ]
            ],
            [
            "label" => [
                "en" => "Creator",
                "es" => "Creador"
            ],
            "value" => [
                "en" => $acf_fields['ncma_creator'],
                ]
            ],
            [
            "label" => [
                "en" => "Object Number",
                "es" => "Número de objeto"
            ],
            "value" => [
                "en" => $acf_fields['ncma_object_number'],
                ]
            ],
        ],
        "rights" => $acf_fields['ncma_rights_url'],
        "requiredStatement" => [ 
            "label" => [
                "en" => "Required Statement",
                "es" => "Declaración requerida"
            ],
            "value" => [
                "en" => $acf_fields['ncma_required_statement_en'],
                "es" => $acf_fields['ncma_required_statement_es']
                ]
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
        // add annotations
        "annotations" => transformAnnotationsForIIIF($acf_fields, $base_uri, $image_width, $image_height)
    ];

    $manifest["items"][] = $canvas;

    return $manifest;
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
function transformAnnotationsForIIIF($acf_fields, $base_uri, $canvas_width, $canvas_height)
{
    $annotations = $acf_fields['ncma_annotated_image_annotations'] ?? [];

    $annotation_items = array_map(function ($annotation) use ($base_uri, $canvas_width, $canvas_height) {
        $coordinates = parseCoordinates($annotation['ncma_annotation_coordinates'] ?? '0%,0%', $canvas_width, $canvas_height);
        $x = $coordinates['x'];
        $y = $coordinates['y'];

        $annotated_image_accessibility = '';
        $alt_text_english = get_post_meta($annotation['ncma_annotation_related_image'], '_wp_attachment_image_alt', true) ?? '';
        $alt_text_spanish = get_field('media_alt_text_es', $annotation['ncma_annotation_related_image']) ?? '';
        if (!empty($alt_text_english) && !empty($alt_text_spanish)) {
            $annotated_image_accessibility = $alt_text_english . '|' . $alt_text_spanish;
        } elseif (!empty($alt_text_english)) {
            $annotated_image_accessibility = $alt_text_english;
        } elseif (!empty($alt_text_spanish)) {
            $annotated_image_accessibility = $alt_text_spanish;
        } else {
            $annotated_image_accessibility = '';
        }

        $annotation_item = [
            "id" => "{$base_uri}/annotation/{$x}x{$y}",
            "type" => "Annotation",
            "motivation" => "tagging",
            "body" => [],
        ];

        if (!empty($annotation['ncma_annotation_en_description'])) {
            $annotation_item['body'][] = [
                "type" => "TextualBody",
                "value" => $annotation['ncma_annotation_en_description'],
                "label" => $annotation['ncma_annotation_en_title'],
                "format" => "text/html",
                "language" => "en"
            ];
        }
        if (!empty($annotation['ncma_annotation_es_description'])) {
            $annotation_item['body'][] = [
                "type" => "TextualBody",
                "value" => $annotation['ncma_annotation_es_description'],
                "label" => $annotation['ncma_annotation_es_title'],
                "format" => "text/html",
                "language" => "es"
            ];
        }

        if (!empty($annotation['ncma_annotation_related_image'])) {
            $data = [
                "type" => "Image",
                "label" => [
                    "en" => $annotation['ncma_annotation_related_caption_en'],
                    "es" => $annotation['ncma_annotation_related_caption_es']
                ],
                "id" => wp_get_attachment_image_url($annotation['ncma_annotation_related_image'], 'hotspot-related-image'),
                "format" => "image/jpeg"
            ];
            if(!empty($annotated_image_accessibility)) {
                $data['accessibility'] = $annotated_image_accessibility;
            }
            $annotation_item['body'][] = $data;
        }

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

/**
 * Function hooked to run after acf/save_post has saved acf data. 
 * This function will generate a IIIF Manifest JSON file for the Annotated Image post.
 * This file will be saved to the uploads directory in a folder named IIIF/ncma-annotated-image/{post_id}.json
 * 
 * @param mixed $post_id
 * @return void
 */
// add_action('acf/save_post', 'save_ncma_annotated_image_iiif_manifest_json', 20, 1); // Uncomment this line to enable the function on ncma-annotated-image save
function save_ncma_annotated_image_iiif_manifest_json($post_id)
{
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


/**
 * Dynamically updates the ACF accordion label for each annotation repeater item
 * to display the value of the "Title (English)" field (ncma_annotation_en_title).
 *
 * This improves admin UX by making it easier to identify collapsed items in long repeaters.
 *
 * Works on initial load and when new repeater rows are added.
 */
add_action('acf/input/admin_footer', function () {
    ?>
    <script>
    (function($) {
        function updateAccordionLabels(context) {
            $(context).find('.acf-field-accordion[data-key="field_ncma_annotation_accordion_ui"]').each(function() {
                var $accordion = $(this);
                var $row = $accordion.closest('.acf-row');

                // Watch for changes to the English title field
                var $titleField = $row.find('[data-key="field_ncma_annotation_en_title"] input');
                console.log($titleField.val());

                function setLabel() {
                    var val = $titleField.val();
                    $accordion.children('.acf-label').find('label').text(val ? val : 'Annotation');
                }

                // Update on change and load
                setLabel();
                $titleField.on('input change', setLabel);
            });
        }

        // Initial load
        $(document).ready(function() {
            updateAccordionLabels(document);
        });

        // When new repeater rows are added
        if (typeof acf !== 'undefined') {
            acf.addAction('append', function($el) {
                updateAccordionLabels($el);
            });
        }
    })(jQuery);
    </script>
    <?php
});

add_action('acf/input/admin_footer', function () {
    ?>
    <script>
    (function($) {
        // Wait until ACF has fully loaded
        acf.addAction('ready', function() {
            // Collapse meta field group
            $('#acf-annotated-image-iiif-meta').addClass('closed');
        });
    })(jQuery);
    </script>
    <?php
});
