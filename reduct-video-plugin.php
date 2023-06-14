<?php
/*
Plugin name: Reduct Video Plugin
Description: Plugin to add reduct video shared video to any WP site
Version: 1.2.2
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
            array('wp-blocks', 'wp-element', 'wp-components'),
            1.0,
            true
        );

        wp_localize_script('blockType', 'WP_PROPS', array('site_url' => get_site_url()));

        // first param -> same as name described in js
        register_block_type("reduct-plugin/configs", array('editor_script' => 'blockType', 'render_callback' => array($this, 'frontendHTML')));
    }

    // attributes are coming from js as params
    function frontendHTML($attributes)
    {
        ob_start();
        include __DIR__ . "/template.php";
        $output = ob_get_clean();
        return $output;
    }

    function transcript_route($request)
    {
        $response = new WP_REST_Response;

        $url_contents = $request->get_params();
        $rest_route = $url_contents["rest_route"];
        $id = explode("/", $rest_route)[4];

        $path = VIDEO_RESOURCE_URL . $id . "/transcript.json";

        $transcript_data = file_get_contents($path);

        if ($transcript_data == false) {
            $response->set_data('not-found');
            $response->set_status(404);
            return $response;
        }

        $response->set_data($transcript_data);
        return $response;
    }


    function video_route($request)
    {
        $response = new WP_REST_Response;

        $url_contents = $request->get_params();

        // instead of separate the url query params, they are packed into rest_route except idx
        $rest_route = $url_contents["rest_route"];

        // extract manifest from url
        $query = parse_url($rest_route, PHP_URL_QUERY);
        parse_str($query, $params);
        $manifest = $params['manifest'];

        // extract transcript id from url
        $id = explode("/", $rest_route)[4];

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
        $prefix_url = 'reduct-plugin/v1';

        register_rest_route(
            $prefix_url,
            '/video/(?P<id>.+)',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'video_route'),
                'permission_callback' => '__return_true'
            )
        );

        register_rest_route(
            $prefix_url,
            '/transcript/(?P<id>.+)',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'transcript_route'),
                'permission_callback' => '__return_true'
            ),
        );
    }


}

$plugin = new Plugin();
?>