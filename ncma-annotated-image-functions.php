<?php
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
    error_log('NCMA: JSON file saved: ' . $json_file);
}
add_action('acf/save_post', 'save_ncma_annotated_image_iiif_manifest_json', 20, 1);

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

function generateIIIFManifest($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return new WP_Error('not_found', 'Post not found', array('status' => 404));
    }

    $acf_fields = function_exists('get_fields') ? get_fields($post_id) : [];
    $manifest_url = get_site_url() . "/wp-content/uploads/IIIF/ncma-annotated-image/{$post_id}.json";

    $manifest = [
        "@context" => "http://iiif.io/api/presentation/3/context.json",
        "id" => $manifest_url,
        "type" => "Manifest",
        "label" => [
            "en" => [$acf_fields['ncma_annotated_image_title']],
            "es" => [$acf_fields['ncma_annotated_image_title_es'] ?? '']
        ],
        "summary" => [
            "en" => [$acf_fields['ncma_annotated_image_en_description']],
            "es" => [$acf_fields['ncma_annotated_image_es_description'] ?? '']
        ],
        "items" => transformAnnotationsForIIIF($acf_fields)
    ];

    return rest_ensure_response($manifest);
}

function transformAnnotationsForIIIF($acf_fields) {
    $annotations = $acf_fields['ncma_annotated_image_annotations'] ?? [];

    return array_map(function ($annotation) {
        $annotation_item = [
            "id" => $annotation['ncma_annotation_coordinates'] ?? null,
            "type" => "Annotation",
            "motivation" => "commenting",
            "body" => []
        ];

        if (!empty($annotation['ncma_annotation_en_description']) || !empty($annotation['ncma_annotation_es_description'])) {
            $annotation_item['body'][] = [
                "type" => "TextualBody",
                "value" => [
                    "en" => $annotation['ncma_annotation_en_description'] ?? '',
                    "es" => $annotation['ncma_annotation_es_description'] ?? ''
                ],
                "format" => "text/plain"
            ];
        }

        if (!empty($annotation['ncma_annotation_related_image'])) {
            $annotation_item['body'][] = [
                "type" => "Image",
                "id" => $annotation['ncma_annotation_related_image'],
                "format" => "image/jpeg"
            ];
        }

        if (!empty($annotation['ncma_annotation_related_caption_en']) || !empty($annotation['ncma_annotation_related_caption_es'])) {
            $annotation_item['body'][] = [
                "type" => "TextualBody",
                "value" => [
                    "en" => $annotation['ncma_annotation_related_caption_en'] ?? '',
                    "es" => $annotation['ncma_annotation_related_caption_es'] ?? ''
                ],
                "format" => "text/plain"
            ];
        }

        $annotation_item["target"] = "#" . ($annotation['ncma_annotation_coordinates'] ?? 'unknown');

        return $annotation_item;
    }, $annotations);
} ?>