<?php

namespace SaltAI\Helper;

use SaltAI\Core\ServiceContainer;

class Image {

    private ServiceContainer $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function get_image_alt(int $post_id, string $lang = ""){
        $meta_key = !empty($lang) ? '_salt_image_alt_'.$lang : "_wp_attachment_image_alt";
        return get_post_meta($post_id, $meta_key, true) ?: null;
    }

    public function update_image_alt(int $post_id, string $meta_value, string $lang = ""): void {
        $plugin = $this->container->get("plugin");
        $meta_key = !empty($lang) ? '_salt_image_alt_'.$lang : "_wp_attachment_image_alt";
        $plugin->log($meta_key." -> ".$meta_value);
        update_post_meta($post_id, $meta_key, $meta_value);
    }

    public function generate_alt_text($attachments = []){
        $plugin = $this->container->get("plugin");
        $integration = $this->container->get("integration");
        $translator = $this->container->get("translator");
        foreach ($attachments as $image) {
            if (!$image["id"]){
                continue;
            }
            $alt_current = $this->get_image_alt($image["id"]);
            if($plugin->options["seo"]["image_alttext"]["preserve"] && !empty($alt_current)){
                $alt = $alt_current;
            }else{
                $alt = $translator->generate_alt_text($image["url"]);
            }
            
            if ($alt) {
                $this->update_image_alt($image["id"], $alt);
                $plugin->log("Alt Text generated for: ".$image["url"]." -> ".$alt); 

                // translate image alt texts to other languages
                if($integration->is_media_translation_enabled() && $plugin->options["seo"]["image_alttext"]["translate"]){
                    $translations = function_exists('pll_get_post_translations') ? pll_get_post_translations($image["id"]) : [];
                    foreach ($translations as $lang => $translated_id) {
                        if ($lang === $integration->default_lang || !$translated_id) continue;
                        $translated_alt = $translator->translate($alt, $lang);
                        $this->update_image_alt($translated_id, $translated_alt);
                        $plugin->log("Alt Text translated to [".$lang."] for: ".$image["url"]." -> ".$translated_alt); 
                    }                
                }
            }
        }
    }

}
