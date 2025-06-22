<?php

namespace SaltAI\Helper;

use SaltAI\Core\ServiceContainer;

class Image {

    private ServiceContainer $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function generate_post_images_alt_text($post_id = 0, $to = 'en') {
        $plugin      = $this->container->get("plugin");
        $integration = $this->container->get("integration");
        $translator  = $this->container->get("translator");

        $post = get_post($post_id);
        if (!$post) return;

        $image_ids = $this->get_post_image_ids($post_id);

        foreach ($image_ids as $item) {
            $id  = $item['id'];
            $url = $item['url'];

            if (!$url || (!$this->is_external($url) && $plugin->isLocalhost())) {
                continue;
            }

            error_log("Alt text yaratılacak > " . $url);
            $alt = $translator->generate_alt_text($url, $to);

            if ($alt && $id) {
                update_post_meta($id, '_wp_attachment_image_alt', $alt);

                $translations = function_exists('pll_get_post_translations') ? pll_get_post_translations($id) : [];
                error_log(":::::::::::: translations:");
                error_log(print_r($translations, true));

                foreach ($translations as $lang => $translated_id) {
                    if ($lang === $integration->default_lang || !$translated_id) continue;
                    $translated_alt = $translator->translate($alt, $lang);
                    update_post_meta($translated_id, '_wp_attachment_image_alt', $translated_alt);
                }
            }
        }
    }

    public function get_post_image_ids($post_id): array {
        $results = [];

        $post = get_post($post_id);
        if (!$post) return [];

        // Gutenberg blocks
        $blocks = parse_blocks($post->post_content);
        $results = array_merge($results, $this->extract_images_from_blocks($blocks));

        // Raw <img> in content
        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $post->post_content, $matches);
        foreach ($matches[1] as $src) {
            $results[] = [
                'id'  => attachment_url_to_postid($src) ?: null,
                'url' => $src
            ];
        }

        // ACF
        if (function_exists('get_fields')) {
            $acf_fields = get_fields($post_id);
            $results = array_merge($results, $this->extract_images_from_acf($acf_fields));
        }

        // Unique by URL
        $unique = [];
        foreach ($results as $img) {
            if (!empty($img['url'])) {
                $unique[$img['url']] = $img;
            }
        }

        return array_values($unique);
    }

    public function extract_images_from_blocks(array $blocks): array {
        $results = [];

        foreach ($blocks as $block) {
            if (!empty($block['attrs'])) {
                foreach ($block['attrs'] as $key => $value) {
                    if (in_array($key, ['id', 'imageId', 'mediaId']) && is_numeric($value)) {
                        $url = wp_get_attachment_image_src($value, 'full')[0] ?? '';
                        $results[] = [
                            'id'  => (int) $value,
                            'url' => $url
                        ];
                    }
                }
            }

            if (!empty($block['innerHTML'])) {
                preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $block['innerHTML'], $matches);
                foreach ($matches[1] as $src) {
                    $results[] = [
                        'id'  => attachment_url_to_postid($src) ?: null,
                        'url' => $src
                    ];
                }
            }

            if (!empty($block['innerBlocks'])) {
                $results = array_merge($results, $this->extract_images_from_blocks($block['innerBlocks']));
            }
        }

        return $results;
    }

    public function extract_images_from_acf($fields): array {
        $results = [];

        if (is_array($fields)) {
            foreach ($fields as $value) {
                if (is_numeric($value)) {
                    $attachment = get_post((int) $value);
                    if ($attachment && $attachment->post_type === 'attachment') {
                        $url = wp_get_attachment_image_src($value, 'full')[0] ?? '';
                        $results[] = [
                            'id'  => (int) $value,
                            'url' => $url
                        ];
                    }
                } elseif (is_array($value)) {
                    $results = array_merge($results, $this->extract_images_from_acf($value));
                }
            }
        }

        return $results;
    }

    public function is_external($url): bool {
        $host      = parse_url($url, PHP_URL_HOST);
        $site_host = parse_url(home_url(), PHP_URL_HOST);
        return $host && $host !== $site_host;
    }
}
