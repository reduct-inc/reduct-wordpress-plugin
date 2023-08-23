<?php
/*
Plugin Name: Reduct SearchWP configs
Description: Customizations for searchWP plugin to include search result from the embed transcript
Version: 1.0.0
*/

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