<?php
/*
Plugin Name: Reduct Video Reel Embed Elementor Widget
Description: Plugin to add reduct video shared video to any WP site
Author: Reduct Video
 */

if ( ! defined( 'ABSPATH' ) ) {
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

function register_reduct_reel_embed_widget( $widgets_manager ) {

	require_once( __DIR__ . '/widgets/reduct-embed-elementor-widget.php' );

	$widgets_manager->register( new \Elementor_Reduct_Reel_Embed_Widget() );

}

add_action( 'elementor/widgets/register', 'register_reduct_reel_embed_widget' );