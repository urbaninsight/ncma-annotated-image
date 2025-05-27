<?php

/**
 * Parses a coordinate string like '24.123%,75.001%' into pixel values.
 *
 * @param string $coordinate_string The coordinate string from ACF.
 * @param int    $canvas_width      The width of the canvas in pixels.
 * @param int    $canvas_height     The height of the canvas in pixels.
 * @return array Associative array with 'x' and 'y' pixel values.
 */
function parseCoordinates($coordinate_string, $canvas_width, $canvas_height)
{
    $coordinates = explode(',', $coordinate_string);
    $x_percent = trim($coordinates[0] ?? '0%');
    $y_percent = trim($coordinates[1] ?? '0%');

    return [
        'x' => convertPercentToPixel($x_percent, $canvas_width),
        'y' => convertPercentToPixel($y_percent, $canvas_height)
    ];
}
/**
 * Converts a percentage string to a pixel value based on the given dimension.
 *
 * @param string $percent The percentage string (e.g., "45%").
 * @param int $dimension The total dimension (width or height) in pixels.
 * @return int The computed pixel value casted as
 */
function convertPercentToPixel($percent, $dimension)
{
    $percent_value = floatval(str_replace('%', '', $percent));
    return round(($percent_value / 100) * $dimension);
}

/**
 * Appends additional parameters to the iframe src URL and adds attributes to the iframe HTML.
 * Used primarily for the description video field in ACF.
 * @param mixed $iframe
 * @return array|string
 */
function ui_ncma_add_video_attributes($iframe)
{
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
 * Helper function that converts a media ID to an array of image URLs for various sizes.
 * @param mixed $image_id
 * @return array|null
 */
function ui_get_image_urls_from_id($image_id)
{
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


// Debug ----------------------------------------------------------------------
// Modified from ncma-digital-label

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