<?php
/*
Plugin name: Reduct Video Reel Embed Elementor Widget
Description: Plugin to add reduct video shared video to any WP site
Author: Reduct Video
*/

if (!defined('ABSPATH')) // exit if try to access from the browser directly
	exit;


// Enqueue custom JavaScript file



class Elementor_Reduct_Reel_Embed_Widget extends \Elementor\Widget_Base
{
	public function get_name()
	{
		return 'Reduct Embed';
	}

	public function get_title()
	{
		return esc_html__('Reduct Embed', 'reduct-embed-elementor');
	}

	public function get_icon()
	{
		return 'eicon-video-playlist';
	}
	public function get_categories()
	{
		return ['general'];
	}

	public function get_keywords()
	{
		return ['embed', 'reduct', 'reel', 'url', 'link'];
	}

	protected function register_controls()
	{

		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__('Content', 'reduct-embed-elementor'),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'url',
			[
				'label' => esc_html__('URL to embed', 'reduct-embed-elementor'),
				'type' => \Elementor\Controls_Manager::TEXT,
				'placeholder' => esc_html__('https://reel-link.com', 'reduct-embed-elementor'),

			]
		);

		$this->add_control(
			'transcriptHeight',
			[
				'label' => esc_html__('Transcript Height (px):', 'reduct-embed-elementor'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'step' => 1,
				'range' => [
					'px' => [
						'min' => 160,
						'max' => 400,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 160,
				],
				'selectors' => [
					'{{WRAPPER}} .reduct-plugin-transcript-wrapper' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'borderRadius',
			[
				'label' => esc_html__('Border Radius (px):', 'reduct-embed-elementor'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'step' => 1,
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 40,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 22,
				],
				'selectors' => [
					'{{WRAPPER}} .reduct-plugin-container' => 'border-radius: {{SIZE}}px;',
				],
			]
		);

		$this->add_control(
			'reductDomElement',
			[
				'label' => esc_html__('Reduct DOM Element', 'reduct-embed-elementor'),
				'type' => \Elementor\Controls_Manager::HIDDEN,
				"input_type" => "text",

			]
		);

		$this->add_control(
			'uniqueId',
			[
				'label' => esc_html__('Unique ID', 'reduct-embed-elementor'),
				'type' => \Elementor\Controls_Manager::HIDDEN,
				"input_type" => "text",
			]
		);

		$this->add_control(
			'highlightColor',
			[
				'label' => esc_html__('Highlight Color', 'reduct-embed-elementor'),
				'type' => \Elementor\Controls_Manager::HIDDEN,
				"input_type" => "text",
				'default' => '#FCA59C',
			]
		);

		$this->add_control(
			'embedReel',
			[
				'label' => esc_html__('', 'reduct-embed-elementor'),
				'type' => \Elementor\Controls_Manager::BUTTON,
				'button_type' => 'success',
				'text' => esc_html__('Embed', 'reduct-embed-elementor'),
				'event' => 'embedReductReelsButtonEvent',
			]
		);

		$this->end_controls_section();
	}

	protected function render()
	{
		$settings = $this->get_settings_for_display();


		$attributes = array("url" => $settings['url'], "domElement" => $settings['reductDomElement'], "uniqueId" => $settings["uniqueId"], "transcriptHeight" => $settings["transcriptHeight"], "highlightColor" => $settings["highlightColor"], "borderRadius" => $settings["borderRadius"]);

		$highlightColor = $attributes["highlightColor"];
		$transcriptHeight = $attributes["transcriptHeight"];

		// adding "/" if url is missing it
		$base_url = $attributes["url"];
		$domElement = $attributes["domElement"];
		$borderRadius = $attributes["borderRadius"];
		$id = $attributes["uniqueId"];


		$site_url = get_site_url();

		if (!str_ends_with($attributes["url"], "/")) {
			$base_url = $attributes["url"] . "/";
		}

		$manifest = file_get_contents($base_url . "burn?type=json");
		?>
		<script>
			(function () {
				const WP_PROPS = {
					id: "<?php echo $id ?>",
					site_url: "<?php echo $site_url ?>",
					stringifiedManifest: `<?php echo $manifest ?>`,
					transcriptHeight: `<?php echo $transcriptHeight["size"] . $transcriptHeight["unit"] ?>`,
					highlightColor: `<?php echo $highlightColor ?>`,
					transcriptUrl: `<?php echo $base_url ?>`,
					borderRadius: `<?php echo strval($borderRadius) . "px" ?>`
				};

				<?php echo file_get_contents(dirname(__FILE__) . "/../src/videoLoadScript.js") ?>
			})();
		</script>
		<?php
		require dirname(__FILE__) . "/../template.php";
	}

	protected function content_template()
	{
		?>
		<div>
			<# var domElement=settings.reductDomElement; #>
				{{{domElement}}}
		</div>
		<?php
	}
}