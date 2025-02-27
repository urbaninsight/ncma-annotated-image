<?php

function ncma_annotated_image_update_slug( $data, $postarr ) {

    if ($data["post_type"] == "ncma-annotated-image") {

        // $post_name is the slug, sanitized for unique usage in URL by sanitize title
        $data["post_name"] = sanitize_title( $data["post_title"] );
    
    }

    return $data;
}

add_filter( "wp_insert_post_data", "ncma_annotated_image_update_slug", 99, 2 );