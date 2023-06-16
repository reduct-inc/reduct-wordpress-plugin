<?php
/*
Plugin name: Reduct Video Reel Embed Elementor Widget
Description: Plugin to add reduct video shared video to any WP site
Author: Reduct Video
*/

if (!defined('ABSPATH')) // exit if try to access from the browser directly
    exit;

class Elementor_Reduct_Reel_Embed_Widget extends \Elementor\Widget_Base {

    /**
	 * Get widget name.
	 *
	 * Retrieve reduct reel embed widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget name.
	 */

	public function get_name() {
		return 'reductReelEmbed';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve reduct reel embed widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget title.
	 */

	public function get_title() {
		return esc_html__( 'reductReelEmbed', 'reduct-embed-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve reduct reel embed widget  icon.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string Widget icon.
	 */

	public function get_icon() {
		return 'eicon-code';
	}

    /**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the oEmbed widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'general' ];
	}

    /**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the reduct reel embed widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array Widget keywords.
	 */
    
	public function get_keywords() {
		return [ 'embed', 'reduct', 'reel', 'url', 'link' ];
	}

    function __construct()
    {
        add_action('init', array($this, 'adminAssets'));
        add_action('rest_api_init', array($this, 'rest_api_routes'));
    }

    /**
	 * Get script handles on which widget depends.
	 *
	 * @since 1.0.0
	 *
	 * @return array Script handles.
	 */
	public function get_script_depends() {
		return array( 'reduct-elementor' );
	}

    /**
	 * Register reduct reel embed widget controls.
	 *
	 * Add input fields to allow the user to customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */

	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'reduct-embed-elementor' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'url',
			[
				'label' => esc_html__( 'URL to embed', 'reduct-embed-elementor' ),
				'type' => \Elementor\Controls_Manager::TEXT,
				'input_type' => 'url',
				'placeholder' => esc_html__( 'https://reel-link.com', 'reduct-embed-elementor' ),
			]
		);

		$this->end_controls_section();

	}

    function adminAssets(){
		wp_register_script( 'reduct-elementor', dirname(__DIR__) . '/src/reductElementor.js');
        wp_enqueue_script('reduct-elementor');
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

    /**
	 * Render reduct video reel embed widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */

	protected function render() {

		$settings = $this->get_settings_for_display();
		$html = wp_reduct_embed_get( $settings['url'] );

		echo '<div class="reduct-embed-elementor">';
		echo ( $html ) ? $html : $settings['url'];
		echo '</div>';
	}

    // Render the widget output in the editor
    protected function _content_template() {
		echo '<div>';
		echo '</div>';
    }

}