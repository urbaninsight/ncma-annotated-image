<?php
function save_ncma_annotated_image_json($post_id, $post) {
    // Ensure we're only working with the correct post type
    if ($post->post_type !== 'ncma-annotated-image') {
        return;
    }
    
    // Ensure the post is published
    if ($post->post_status !== 'publish') {
        return;
    }
    
    // Call the function to get the JSON data
    $request = new WP_REST_Request('GET', '/wp/v2/ncma-annotated-image/' . $post_id);
    $json_data = ui_ncma_annotated_image_data($request);
    
    if (empty($json_data) || !is_array($json_data)) {
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
add_action('save_post_ncma-annotated-image', 'save_ncma_annotated_image_json', 10, 2);