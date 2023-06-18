<?php
/*
Plugin Name: Reduct Video Reel Embed Elementor Widget
Description: Plugin to add reduct video shared video to any WP site
Version: 1.3.0
Author: Reduct Video
*/

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Register Reduct Video Reel Embed Elemntor Widget
 *
 * Include widget file and register widget class.
 *
 * @since 1.0.0
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 * @return void
 */

function register_reduct_reel_embed_widget($widgets_manager)
{

	require_once(__DIR__ . '/reduct-embed-elementor-widget.php');

	$widgets_manager->register(new \Elementor_Reduct_Reel_Embed_Widget());
}

function enqueue_custom_script()
{
	wp_enqueue_script(
		'me',
		plugin_dir_url(__FILE__) . 'build/elementorWidget.js',
		array('jquery','wp-blocks', 'wp-element', 'wp-components'),
		'1.0.0',
		true
	);

	wp_localize_script('me', 'WP_PROPS', array('site_url' => get_site_url()));
}
add_action('elementor/frontend/after_enqueue_scripts', 'enqueue_custom_script');


add_action('elementor/widgets/register', 'register_reduct_reel_embed_widget');