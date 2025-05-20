<?php
/*
Plugin name: Reduct Video Plugin
Description: Plugin to add reduct video shared video to any WP site
Version: 2.1.1
Author: Reduct Video
*/

if (!defined('ABSPATH')) // exit if try to access from the browser directly
    exit;

define("VIDEO_RESOURCE_URL", "https://app.reduct.video/e/");
include __DIR__ . "/gutenberg-template.php";

function is_plugin_installed_and_active($plugin)
{
    return in_array($plugin, (array) get_option('active_plugins', array()));
}

class Plugin
{
    function __construct()
    {
        add_action('init', array($this, 'load_gutenberg_block'));

        if (is_plugin_installed_and_active('elementor/elementor.php')) {
            $this->load_elementor_widget();
        }

        if (is_plugin_installed_and_active('searchwp/index.php')) {
            $this->load_searchwp_config();
        }

        // register routes
        add_action('rest_api_init', array($this, 'rest_api_routes'));
    }

    function load_searchwp_config()
    {
        add_filter('searchwp\source\post\attributes\content', function ($content, $args) {
            $post_content = $args['post']->post_content;

            if (strpos($post_content, 'wp:reduct-plugin/configs')) {
                $pattern = '/<!-- wp:reduct-plugin\/configs (.*?)\/-->/s';
                preg_match_all($pattern, $post_content, $matches);

                $contentArrays = $matches[1];

                foreach ($contentArrays as $contentArray) {
                    $config = json_decode(trim($contentArray), true);
                    if ($config !== null) {

                        if (isset($config['reelId'])) {
                            print_r($config);
                            $content .= $config['transcript'];
                        }
                    }
                }
            }

            return $content;
        }, 20, 2);
    }

    function load_gutenberg_block()
    {
        wp_register_script(
            'blockType' /* name given to JS file */ ,
            plugin_dir_url(__FILE__) . 'build/index.js',
            array('wp-blocks', 'wp-element', 'wp-components'),
            1.0,
            true
        );

        wp_localize_script('blockType', 'WP_PROPS', array('site_url' => get_site_url()));

        wp_enqueue_style('blockType', plugin_dir_url(__FILE__) . 'src/base-style.css', null, 1.0);

        // first param -> same as name described in js
        register_block_type("reduct-plugin/configs", array('editor_script' => 'blockType', 'render_callback' => array($this, 'frontendHTML')));
    }

    // attributes are coming from js as params
    function frontendHTML($attributes)
    {
        // disallow on admin screen
        if (is_admin() || empty($attributes)) {
            return;
        }

        $highlightColor = isset($attributes["highlightColor"]) ? $attributes["highlightColor"] : '#FCA59C';
        $transcriptHeight = isset($attributes["transcriptHeight"]) ? $attributes["transcriptHeight"] : "160px";
        $borderRadius = isset($attributes["borderRadius"]) ? $attributes["borderRadius"] : "22px";
        $base_url = $attributes["url"];

        if (isset($attributes["reelId"]) && isset($attributes["transcript"])) {
            ob_start();
            echo generate_template($attributes["reelId"], $transcriptHeight, $borderRadius, $highlightColor);
            $output = ob_get_clean();
            return $output;
        }

        // add support for legacy version
        $id = $attributes["uniqueId"];
        $domElement = $attributes["domElement"];
        $site_url = get_site_url();

        if (!str_ends_with($attributes["url"], "/")) {
            $base_url = $attributes["url"] . "/";
        }

        $manifest = file_get_contents($base_url . "burn?type=json");

        $segments = array();

        wp_localize_script('video-load-script', 'WP_PROPS', array('highlightColor' => $highlightColor, "transcriptHeight" => $transcriptHeight, "site_url" => get_site_url(), "id" => $id, "stringifiedManifest" => $manifest, "transcriptUrl" => $base_url, "attributes" => $attributes, "borderRadius" => $borderRadius));

        ob_start();
        include __DIR__ . "/template.php";
        $output = ob_get_clean();
        return $output;
    }

    function load_elementor_widget()
    {
        add_action('elementor/widgets/register', array($this, 'register_reduct_reel_embed_widget'));
        add_action('elementor/frontend/after_enqueue_scripts', array($this, 'enqueue_custom_script'));
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
            array('jquery', 'wp-blocks', 'wp-element', 'wp-components'),
            '1.0.0',
            true
        );

        wp_localize_script('elementorWidget', 'WP_PROPS', array('site_url' => get_site_url()));

        wp_enqueue_style('elementorWidget', plugin_dir_url(__FILE__) . 'src/base-style.css', null, 1.0);
    }

    function transcript_route($request)
    {
        $response = new WP_REST_Response;

        $url_contents = $request->get_params();
        $rest_route = $url_contents["rest_route"];
        $id = explode("/", $rest_route)[4];

        $path = VIDEO_RESOURCE_URL . $id . "/transcript.json";

        // supress error with @
        $transcript_data = @file_get_contents($path, null, );

        if ($transcript_data == false) {
            $response->set_data('not-found');
            $response->set_status(404);
            return $response;
        }

        $response->set_data($transcript_data);
        return $response;
    }

    function element_route($request)
    {
        $response = new WP_REST_Response;
        try {
            $urlContents = $request->get_params();

            if (!isset($urlContents["id"])) {
                throw new Exception("Please provide valid id.");
            }

            $reelId = $urlContents["id"];

            $height = isset($urlContents["height"]) ? $urlContents["height"] : "160px";
            $borderRadius = isset($urlContents["borderRadius"]) ? $urlContents["borderRadius"] : "22px";
            $highlightColor = isset($urlContents["highlightColor"]) ? $urlContents["highlightColor"] : '#FCA59C';

            $template = generate_template($reelId, $height, $borderRadius, $highlightColor);

            header('Content-Type: text/html; charset=UTF-8');

            echo $template;
            die();
        } catch (Exception $e) {
            $response->set_data($e->getMessage());
            $response->set_status(500);
            return $response;
        }
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

        $type = isset($params['type']) ? $params['type'] : '';

        // extract transcript id from url
        $id = explode("/", $rest_route)[4];

        $path = "";

        if ($type == "hls") {
            $path = VIDEO_RESOURCE_URL . $id . "/burn?" . "type=" . $type;

            $transcript_data = file_get_contents($path);

            if ($transcript_data == false) {
                $response->set_data('not-found');
                $response->set_status(404);
                return $response;
            }

            header('Content-Type: application/vnd.apple.mpegurl');
            header('Content-Disposition: attachment; filename="burn.m3u"');

            // disable cache
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $transcript_data;
            exit;
        } else {
            $manifest = isset($params['manifest']) ? $params['manifest'] : '';
            $idx = isset($url_contents["idx"]) ? $url_contents["idx"] : '';
            if ($id === "" || $manifest === "" || $idx === "") {
                $response->set_data('not-found');
                $response->set_status(404);
                return $response;
            }

            $path = VIDEO_RESOURCE_URL . $id . "/burn?" . "manifest=" . $manifest . "&idx=" . $idx;
        }

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

        register_rest_route(
            $prefix_url,
            '/element',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'element_route'),
                'permission_callback' => '__return_true'
            ),
        );
    }


}

$plugin = new Plugin();
?>