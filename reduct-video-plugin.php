<?php
/*
Plugin name: Reduct Video Plugin
Description: Plugin to add reduct video shared video to any WP site
Version: 1.3.0
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

        // register elementor widget
        add_action('elementor/widgets/register', array($this, 'register_reduct_reel_embed_widget'));
        add_action('elementor/frontend/after_enqueue_scripts', array($this, 'enqueue_custom_script'));

        // register routes
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

    function register_reduct_reel_embed_widget($widgets_manager)
    {

        require_once(__DIR__ . '/widget/reduct-embed-elementor-widget.php');

        $widgets_manager->register(new \Elementor_Reduct_Reel_Embed_Widget());
    }

    function enqueue_custom_script()
{
	wp_enqueue_script(
		'elementorWidget',
		plugin_dir_url(__FILE__) . 'build/elementorWidget.js',
		array('jquery','wp-blocks', 'wp-element', 'wp-components'),
		'1.0.0',
		true
	);

	wp_localize_script('elementorWidget', 'WP_PROPS', array('site_url' => get_site_url()));
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