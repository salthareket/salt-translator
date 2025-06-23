<?php

namespace SaltAI\Translator;

use SaltAI\Core\ServiceContainer;

// Translator name: OpenAI

class Translator {
    private ServiceContainer $container;

    public $temperatures;
    public $temperature;
    public $temperature_meta_desc;
    public $temperature_image_alttext;
    public $models;
    public $model;
    public $model_meta_desc;
    public $model_image_alttext;
    public $custom_prompt;
    private $api_url;

    public function __construct($container) {
        $this->container = $container;
        //$this->plugin = $container->get('plugin');
        $this->models = [
            'gpt-3.5-turbo' => [
                'name' => 'GPT 3.5 Turbo',
                'input_price' => 0.0005,
                'output_price' => 0.0015,
                'unit' => '1K token',
            ],
            'gpt-4' => [
                'name' => 'GPT-4',
                'input_price' => 0.01,
                'output_price' => 0.03,
                'unit' => '1K token',
            ],
            'gpt-4-turbo' => [
                'name' => 'GPT-4 Turbo',
                'input_price' => 0.01,
                'output_price' => 0.03,
                'unit' => '1K token',
            ],
            'gpt-4o' => [
                'name' => 'GPT-4 Omni',
                'input_price' => 0.005,
                'output_price' => 0.015,
                'unit' => '1K token',
            ],
        ];
        $this->temperatures = [
            '0.0' => __('Exact Copycat', 'salt-ai-translator'),
            '0.1' => __('Literal Bot', 'salt-ai-translator'),
            '0.2' => __('Cautious Thinker', 'salt-ai-translator'),
            '0.3' => __('Professional Advisor', 'salt-ai-translator'),
            '0.4' => __('Curious Analyst', 'salt-ai-translator'),
            '0.5' => __('Balanced Mind', 'salt-ai-translator'),
            '0.6' => __('Creative Helper', 'salt-ai-translator'),
            '0.7' => __('Idea Generator', 'salt-ai-translator'),
            '0.8' => __('Dreamer', 'salt-ai-translator'),
            '0.9' => __('Wild Artist', 'salt-ai-translator'),
            '1.0' => __('Chaos Mode', 'salt-ai-translator'),
            '1.1' => __('Overdrive Madness', 'salt-ai-translator'),
        ];
        $this->model = 'gpt-3.5-turbo';
        $this->model_meta_desc = 'gpt-4';
        $this->model_image_alttext = 'gpt-4';
        $this->temperature = (float) '0.3';
        $this->temperature_meta_desc = (float) '0.5';
        $this->temperature_image_alttext = (float) '0.4';
        $this->api_url = 'https://api.openai.com/v1/chat/completions';
    }

    public function request($body=[]){

        foreach ($this->container->get("plugin")->options["api_keys"]["openai"] as $index => $api_key) {
            if (empty($api_key)) continue;

            $response = wp_remote_post($this->api_url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $api_key,
                    ],
                    'body' => $body,
                    'timeout' => 30,
            ]);

            if (is_wp_error($response)) {
                $this->container->get("plugin")->log("OpenAI Request Error...");
                continue; // WP error varsa diğer key'e geç
            }

            $code = wp_remote_retrieve_response_code($response);
            $data = json_decode(wp_remote_retrieve_body($response), true);

            if ($code === 200 && isset($data['choices'][0]['message']['content'])) {
                //$this->container->get("plugin")->log("output: ".$data['choices'][0]['message']['content']);
                //$this->container->get("plugin")->log("------------------------------------");
                //return $this->decode_html_entities($data['choices'][0]['message']['content']);
                $text = $data['choices'][0]['message']['content'];
                $text = str_replace(['<wrapper>', '</wrapper>'], '', $text);
                return $text;
            }

            // OpenAI kota/limit/plan dolmuş olabilir -> error message kontrolü
            if (!empty($data['error']['code'])) {
                $error_code = $data['error']['code'];
                if (in_array($error_code, ['insufficient_quota', 'invalid_api_key', 'rate_limit_exceeded', '429'])) {
                    continue; // bir sonraki key'e geç
                }
            }
        }

        $this->container->get("plugin")->log("OpenAI Error...");

        return __('', 'salt-ai-translator');
    }

    public function set_custom_prompt($prompt = ""){
        $this->custom_prompt = $prompt;
    }

    public function translate($text = "", $to = 'en'): string {
        $options = $this->container->get("plugin")->options;

        //if (!is_string($text) || trim($text) === '' || !$this->should_translate($text) || empty($text)) return $text;
        $text = is_null($text)?"":$text;
        
        //$text = json_decode('"' . $text . '"');
        //$text = html_entity_decode($text);
        ////$text = $this->decode_html_entities($text);
        //$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $text);
        if (!$this->should_translate($text)) return $text;

        $text = "<wrapper>" . $text . "</wrapper>";
        //$this->container->get("plugin")->log("input: ".$text);

        //$text = $this->decode_html_entities($text);

        //$this->container->get("plugin")->log(" bu bakılıyo: ".$text);

        //$system = "You are a professional translator for website content. Only translate the text without adding, removing, or modifying meaning. Do not generate HTML. Return only the plain translated text.";
        /*$system = "You are a professional website content translator. The input may contain HTML tags. Do NOT translate or alter any HTML tags or attributes. Only translate the visible text between the tags, preserving the exact HTML structure. Never add or remove tags, spaces, or punctuation. Return only the translated content with the original HTML fully intact.  Never encode or escape characters like <, >, &, \", or return unicode sequences like \\u003c.";*/
        $system = "You are a professional website content translator. The input may contain HTML and WordPress shortcodes.
        - Do NOT translate or alter any HTML tags or their attributes.
        - Do NOT translate or alter any WordPress shortcodes (e.g. [shortcode], [contact_form id='x']).
        - Do NOT translate or alter any attribute values such as 'class', 'id', 'data-*', 'href', 'src', 'style', 'title', etc.
        - Only translate the **visible text content** between HTML tags. Keep the original HTML structure exactly the same.
        - Do NOT add or remove any tags, attributes, shortcodes, spaces, or punctuation.
        - NEVER encode or escape characters like <, >, &, ', or return unicode sequences like \\u003c.
        - Return the result as HTML, preserving the original formatting and layout.
        - Even if the input contains a single HTML tag or short snippet, always treat it as full HTML and preserve the entire structure.
        - Always treat the input as HTML, even if it contains a single tag or a short snippet. Do not remove or flatten the structure.
        Your job is to translate only the human-visible content, leaving all structure and code intact.";


            if (!empty($options["prompt"])) {
                $system .= " " . trim($options["prompt"]);
            }
            if (!empty($this->custom_prompt)) {
                $system .= " " . trim($this->custom_prompt);
            }

            $messages = [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Translate the following content to language code [".$to."]: ".trim($text)],
            ];

            $body = json_encode([
                'model' => $options["model"],
                'messages' => $messages,
                'temperature' => 0.3,
            ]);

            return $this->request($body);
    }
    public function should_translate($text) {
        if (!is_string($text) || trim($text) === '' || is_null($text) || empty($text) || $text === "null" || $text === "NULL") {
            return false;
        }

        $stripped = trim(strip_tags($text));

        if ($stripped === '') {
            return false;
        }

        if (is_numeric($stripped)) {
            return false;
        }

        return true;
    }

    public function decode_html_entities($text) {
        return str_replace(
            ['\\u003c', '\\u003e', '\\u0026', '\\u0022', '\\"', "\\n", "\\t"],
            ['<', '>', '&', '"', '"', '', ''],
            $text
        );
    }

    public function generate_alt_text(string $image_url, $to=""): string {
        $default_language = $this->container->get("integration")->default_language;
        if(empty($to) || !isset($to)){
            $to = $default_language;
        }
        $options = $this->container->get("plugin")->options;
        if (!is_string($image_url) || trim($image_url) === '' || empty($image_url) || empty($options["api_keys"]["openai"])) return '';

        $system = "Generate a concise ALT text (max 1 sentence) in language code [".$to."] for accessibility. Don't use 'image of' or 'photo of'.";
        if(!empty($options["seo"]["image_alttext"]["prompt"])){
            $system .= $options["seo"]["image_alttext"]["prompt"];
        }

        $body = json_encode([
            "model" => "gpt-4o",
            "messages" => [[
                "role" => "user",
                "content" => [
                    ["type" => "text", "text" => $system],
                    ["type" => "image_url", "image_url" => ["url" => $image_url]]
                ]
            ]],
            "temperature" => (float) $options["seo"]["image_alttext"]["temperature"] ?? 0.4,
        ]);

        return $this->request($body);
    }

    private function get_usable_key() {
        foreach ($this->container->get("plugin")->options["api_keys"]["openai"] as $key) {
            if (!empty($key)) return $key;
        }
        return false;
    }

    public function quota_info($keys = []) {
        $results = [];

        foreach ($keys as $i => $key) {
            $key = trim($key);
            if (empty($key)) {
                $results[] = __('Empty key skipped.', 'salt-ai-translator');
                continue;
            }

            $response = wp_remote_get('https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type'  => 'application/json',
                ],
                'timeout' => 20,
            ]);

            if (is_wp_error($response)) {
                $results[] = __('API error – ', 'salt-ai-translator') . $response->get_error_message();
                continue;
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                $results[] = __('Valid', 'salt-ai-translator');
            } else {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                $msg = $body['error']['message'] ?? __('Unknown error', 'salt-ai-translator');
                $results[] = __('API error – ', 'salt-ai-translator') . $msg;
            }
        }

        return $results;
    }
}
