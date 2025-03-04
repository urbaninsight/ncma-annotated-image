<?php
/*
Plugin Name: NCMA Annotated Image Post Type
Description: Plugin to register the ncma-annotated-image post type. Makes the ncma-annotated-image post type available, creates its Advanced Custom Fields, and creates a custom endpoint for the REST API.
Version: 0.1
Author: Urban Insight
*/

// Require the scripts to execute them
// dirname(__FILE__) represents the current directory
require_once(dirname(__FILE__) . '/ncma-annotated-image-utilities.php');
require_once(dirname(__FILE__) . '/ncma-annotated-image-register-posttype.php');
require_once(dirname(__FILE__) . '/ncma-annotated-image-rest-api-endpoint.php');
require_once(dirname(__FILE__) . '/ncma-annotated-image-clone-post-draft.php');
require_once(dirname(__FILE__) . '/ncma-annotated-image-update-permalink-slug.php');
require_once(dirname(__FILE__) . '/image-hotspots/acf-image-hotspots.php');
require_once(dirname(__FILE__) . '/ncma-annotated-image-functions.php');