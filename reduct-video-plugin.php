<?php
/*
Plugin name: Reduct Video Plugin
Description: Plugin to add reduct video shared video to any WP site
Version: 1.0
Author: Reduct Video
*/

if (!defined('ABSPATH')) // exit if try to access from the browser directly
    exit;

define("VIDEO_RESOURCE_URL", "https://app.reduct.video/e/");

class Plugin
{
    function __construct()
    {
        add_action('init', array($this, 'adminAssets'));
        add_action('rest_api_init', array($this, 'rest_api_routes'));
    }

    function adminAssets()
    {
        wp_register_script(
            'blockType' /* name given to JS file */,
            plugin_dir_url(__FILE__) . 'build/index.js',
            array('wp-blocks', 'wp-element', 'wp-components') 
        );

        // first param -> same as name described in js
        register_block_type("reduct-plugin/configs", array('editor_script' => 'blockType', 'render_callback' => array($this, 'frontendHTML')));
    }

    // attributes are coming from js as params
    function frontendHTML($attributes)
    {
        ob_start();
        include __DIR__ . "/template.php";
        return ob_get_clean();
    }


    function video_route($request)
    {
        $response = new WP_REST_Response;

        $url_contents = $request-> get_params();

        $id = str_replace("/burn","",$url_contents["id"])  ;
        $manifest = $url_contents["manifest"];
        $idx = $url_contents["idx"];

        if ($id === "" || $manifest === "" || $idx === "") {
            $response->set_data('not-found');
            $response->set_status(404);
            return $response;
        }

        $path = VIDEO_RESOURCE_URL . $id . "/burn?" . "manifest=" . $manifest . "&idx=" . $idx;

        $video_data = file_get_contents($path);

        // in case there is no video from the url
        if ($video_data == false) {
            $response->set_data('not-found');
            $response->set_status(404);
            return $response;
        }

        $response->set_data($video_data);
        $response->set_headers([
            'Content-Type' => "video/mp4",
        ]);

        add_filter('rest_pre_serve_request', array($this, "serveVideo"), 0, 2);
        return $response;
    }

    function serveVideo($served, $result)
    {
        $is_video = false;
        $video_data = null;

        foreach ($result->get_headers() as $header => $value) {
            if ('content-type' === strtolower($header)) {
                $is_video = 0 === strpos($value, 'video/');
                $video_data = $result->get_data();
                break;
            }
        }

        if ($is_video && is_string($video_data)) {
            echo $video_data;

            return true;
        }

        return $served;
    }

    function rest_api_routes()
    {
        register_rest_route(
            'reduct-plugin/v1',
            '/video/(?P<id>.+)',
            array(
                // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
                'methods' => WP_REST_Server::READABLE,
                // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
                'callback' => array($this, 'video_route')
            )
        );
    }


}

$plugin = new Plugin();