<?php
/**
 * Plugin Name: Imaxio SEO Image Renamer and Alt Text
 * Description: Optimize your image SEO (Filenames and Alt texts) via AI (OpenAI, Google Gemini, or Claude) directly from the WordPress media library in one click.
 * Version: 1.0
 * Author: GuilhemF
 * Author URI: https://guilhemf.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Security: prevent direct access
}

// -------------------------------------------------------------------
// 1. Add menu under Settings
// -------------------------------------------------------------------
add_action('admin_menu', 'imaxio_irat_add_admin_menu');
function imaxio_irat_add_admin_menu() {
    add_options_page(
        'Imaxio SEO Image Renamer', 
        'Imaxio Image SEO',         
        'manage_options',           
        'imaxio-image-rename',      
        'imaxio_irat_options_page_html'
    );
}

// -------------------------------------------------------------------
// 2. Register settings
// -------------------------------------------------------------------
add_action('admin_init', 'imaxio_irat_settings_init');
function imaxio_irat_settings_init() {
    register_setting('imaxio_irat_plugin_options', 'imaxio_irat_api_provider', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('imaxio_irat_plugin_options', 'imaxio_irat_api_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('imaxio_irat_plugin_options', 'imaxio_irat_project_scope', ['sanitize_callback' => 'sanitize_textarea_field']);

    add_settings_section(
        'imaxio_irat_plugin_main_section',
        'API and Context Settings',
        '__return_empty_string',
        'imaxio_irat_plugin'
    );

    add_settings_field(
        'imaxio_irat_api_provider_field',
        'AI Provider',
        'imaxio_irat_api_provider_render',
        'imaxio_irat_plugin',
        'imaxio_irat_plugin_main_section'
    );

    add_settings_field(
        'imaxio_irat_api_key_field',
        'API Key',
        'imaxio_irat_api_key_render',
        'imaxio_irat_plugin',
        'imaxio_irat_plugin_main_section'
    );

    add_settings_field(
        'imaxio_irat_project_scope_field',
        'Project Description (Scope)',
        'imaxio_irat_project_scope_render',
        'imaxio_irat_plugin',
        'imaxio_irat_plugin_main_section'
    );
}

function imaxio_irat_api_provider_render() {
    $provider = get_option('imaxio_irat_api_provider', 'gemini');
    ?>
    <select name="imaxio_irat_api_provider" id="imaxio_irat_api_provider">
        <option value="gemini" <?php selected($provider, 'gemini'); ?>>Gemini (Google)</option>
        <option value="openai" <?php selected($provider, 'openai'); ?>>ChatGPT (OpenAI)</option>
        <option value="claude" <?php selected($provider, 'claude'); ?>>Claude (Anthropic)</option>
    </select>
    <?php
}

function imaxio_irat_api_key_render() {
    $apiKey = get_option('imaxio_irat_api_key');
    echo "<input type='password' name='imaxio_irat_api_key' id='imaxio_irat_api_key' value='" . esc_attr($apiKey) . "' style='width: 100%; max-width: 400px;' placeholder='Enter your API key...' />";
}

function imaxio_irat_project_scope_render() {
    $scope = get_option('imaxio_irat_project_scope');
    echo "<textarea name='imaxio_irat_project_scope' id='imaxio_irat_project_scope' rows='5' style='width: 100%; max-width: 400px;' placeholder='Ex: Context: Niche perfume e-shop. Target: Young audience...'>" . esc_textarea($scope) . "</textarea>";
    echo "<p class='description'>This text provides context to the AI for generating SEO-friendly filenames and alt tags.</p>";
}

// -------------------------------------------------------------------
// 3. Display Settings Page HTML
// -------------------------------------------------------------------
function imaxio_irat_options_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h2>Imaxio SEO Image Renamer and Alt Text</h2>
        <form action="options.php" method="post" id="imaxio-irat-settings-form">
            <?php
            settings_fields('imaxio_irat_plugin_options');
            do_settings_sections('imaxio_irat_plugin');
            submit_button('Save Settings');
            ?>
        </form>

        <hr style="margin: 30px 0;">
        
        <h3>API Connection Test</h3>
        <p>Verify that your API key is valid before using the media library.</p>
        <button type="button" id="imaxio-irat-test-btn" class="button button-secondary">Test API Connection</button>
        <span id="imaxio-irat-test-result" style="margin-left: 10px; font-weight: bold;"></span>
    </div>
    <?php
}

// -------------------------------------------------------------------
// 4. Scripts Injection (Strict WP.org Standard)
// -------------------------------------------------------------------
add_action('admin_enqueue_scripts', 'imaxio_irat_enqueue_admin_scripts');
function imaxio_irat_enqueue_admin_scripts($hook) {
    // Register a dummy script to attach inline JS securely
    wp_register_script('imaxio-irat-dummy-script', '', [], '1.0', true);

    // Settings Page Script
    if ($hook === 'settings_page_imaxio-image-rename') {
        wp_enqueue_script('imaxio-irat-dummy-script');
        $nonce_test = esc_js(wp_create_nonce('imaxio_irat_test_nonce'));
        $ajax_url = esc_url(admin_url('admin-ajax.php'));
        
        $js_settings = "
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('imaxio-irat-test-btn');
            if(btn) {
                btn.addEventListener('click', function() {
                    var resultSpan = document.getElementById('imaxio-irat-test-result');
                    var apiKey = document.getElementById('imaxio_irat_api_key').value;
                    var apiProvider = document.getElementById('imaxio_irat_api_provider').value;

                    if (!apiKey) {
                        resultSpan.innerHTML = '<span style=\"color:#d63638;\">Please enter your API key first.</span>';
                        return;
                    }

                    this.disabled = true;
                    resultSpan.innerHTML = 'Testing connection...';

                    var formData = new FormData();
                    formData.append('action', 'imaxio_irat_test_api');
                    formData.append('api_key', apiKey);
                    formData.append('api_provider', apiProvider);
                    formData.append('nonce', '{$nonce_test}');

                    fetch('{$ajax_url}', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        btn.disabled = false;
                        if(data.success) {
                            resultSpan.innerHTML = '<span style=\"color:#00a32a;\">' + data.data.message + '</span>';
                        } else {
                            resultSpan.innerHTML = '<span style=\"color:#d63638;\">' + data.data.message + '</span>';
                        }
                    })
                    .catch(error => {
                        btn.disabled = false;
                        resultSpan.innerHTML = '<span style=\"color:#d63638;\">Server communication error.</span>';
                    });
                });
            }
        });";
        wp_add_inline_script('imaxio-irat-dummy-script', $js_settings);
    }

    // Media Library Script
    if (get_option('imaxio_irat_api_connected') == 1 && ($hook === 'upload.php' || $hook === 'post.php')) {
        wp_enqueue_script('imaxio-irat-dummy-script');
        $nonce_process = esc_js(wp_create_nonce('imaxio_irat_process_nonce'));
        $ajax_url = esc_url(admin_url('admin-ajax.php'));

        $js_media = "
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('imaxio-irat-media-btn')) {
                e.preventDefault();
                var btn = e.target;
                var postId = btn.getAttribute('data-id');
                var statusSpan = document.getElementById('imaxio-irat-status-' + postId);
                
                btn.disabled = true;
                statusSpan.style.color = '#2271b1';
                statusSpan.innerText = 'Analyzing image...';

                var formData = new FormData();
                formData.append('action', 'imaxio_irat_process_image');
                formData.append('post_id', postId);
                formData.append('nonce', '{$nonce_process}');

                fetch('{$ajax_url}', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    btn.disabled = false;
                    if(data.success) {
                        statusSpan.style.color = '#00a32a';
                        statusSpan.innerText = 'Success! File & Meta updated.';

                        if (typeof wp !== 'undefined' && wp.media && wp.media.frame) {
                            try {
                                var selection = wp.media.frame.state().get('selection');
                                if (selection && selection.first) {
                                    var attachment = selection.first();
                                    if (attachment) {
                                        attachment.set({
                                            title: data.data.new_title,
                                            alt: data.data.new_alt,
                                            url: data.data.new_url,
                                            filename: data.data.new_filename
                                        });
                                    }
                                }
                            } catch(e) {}
                        }

                        var row = btn.closest('tr');
                        if (row) {
                            var titleLink = row.querySelector('.column-title strong a');
                            if (titleLink) { titleLink.textContent = data.data.new_title; }

                            var filenameP = row.querySelector('.filename');
                            if (filenameP) {
                                var srSpan = filenameP.querySelector('.screen-reader-text');
                                if (srSpan && srSpan.nextSibling) {
                                    srSpan.nextSibling.textContent = ' ' + data.data.new_filename;
                                }
                            }
                        }

                        var infoPanel = btn.closest('.attachment-info');
                        if (infoPanel) {
                            var titleField = infoPanel.querySelector('[data-setting=\"title\"] input, [data-setting=\"title\"] textarea');
                            if (titleField) { titleField.value = data.data.new_title; }

                            var altField = infoPanel.querySelector('[data-setting=\"alt\"] textarea');
                            if (altField) { altField.value = data.data.new_alt; }

                            var urlField = infoPanel.querySelector('[data-setting=\"url\"] input');
                            if (urlField) { urlField.value = data.data.new_url; }

                            var filenameDiv = infoPanel.querySelector('.filename');
                            if (filenameDiv) {
                                var strongEl = filenameDiv.querySelector('strong');
                                if (strongEl && strongEl.nextSibling) {
                                    strongEl.nextSibling.textContent = ' ' + data.data.new_filename;
                                } else if (!strongEl) {
                                    filenameDiv.textContent = data.data.new_filename;
                                }
                            }
                        }

                        var postTitleInput = document.getElementById('title');
                        if (postTitleInput) { postTitleInput.value = data.data.new_title; }
                    } else {
                        statusSpan.style.color = '#d63638';
                        statusSpan.innerText = 'Error: ' + data.data.message;
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    statusSpan.style.color = '#d63638';
                    statusSpan.innerText = 'Connection error.';
                });
            }
        });";
        wp_add_inline_script('imaxio-irat-dummy-script', $js_media);
    }
}

// -------------------------------------------------------------------
// 5. AJAX Endpoint for testing
// -------------------------------------------------------------------
add_action('wp_ajax_imaxio_irat_test_api', 'imaxio_irat_test_api_callback');
function imaxio_irat_test_api_callback() {
    check_ajax_referer('imaxio_irat_test_nonce', 'nonce');

    $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
    $api_provider = isset($_POST['api_provider']) ? sanitize_text_field(wp_unslash($_POST['api_provider'])) : 'gemini';
    
    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API Key is missing.']);
    }

    if ($api_provider === 'gemini') {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key;
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            update_option('imaxio_irat_api_connected', 0);
            wp_send_json_error(['message' => 'HTTP Error: ' . $response->get_error_message()]);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code === 200) {
            update_option('imaxio_irat_api_connected', 1);

            $selected_model = 'gemini-2.5-flash';
            if (isset($data['models']) && is_array($data['models'])) {
                $flash_models = [];
                foreach ($data['models'] as $model) {
                    if (strpos($model['name'], 'flash') !== false && strpos($model['name'], 'omni') === false && strpos($model['name'], 'preview') === false && isset($model['supportedGenerationMethods']) && in_array('generateContent', $model['supportedGenerationMethods'])) {
                        $flash_models[] = str_replace('models/', '', $model['name']);
                    }
                }
                if (!empty($flash_models)) {
                    rsort($flash_models);
                    $selected_model = $flash_models[0];
                }
            }
            update_option('imaxio_irat_gemini_model', $selected_model);

            wp_send_json_success(['message' => 'Success! Connected to Gemini. Auto-selected model: ' . $selected_model]);
        } else {
            update_option('imaxio_irat_api_connected', 0);
            wp_send_json_error(['message' => 'Gemini Error: Invalid API Key or request.']);
        }

    } elseif ($api_provider === 'openai') {
        $url = 'https://api.openai.com/v1/models';
        $args = ['headers' => ['Authorization' => 'Bearer ' . $api_key]];
        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            update_option('imaxio_irat_api_connected', 0);
            wp_send_json_error(['message' => 'HTTP Error: ' . $response->get_error_message()]);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code === 200) {
            update_option('imaxio_irat_api_connected', 1);

            $selected_model = 'gpt-4o-mini';
            if (isset($data['data']) && is_array($data['data'])) {
                $available_models = array_column($data['data'], 'id');
                if (in_array('gpt-4o-mini', $available_models)) {
                    $selected_model = 'gpt-4o-mini';
                } elseif (in_array('gpt-4o', $available_models)) {
                    $selected_model = 'gpt-4o';
                }
            }
            update_option('imaxio_irat_openai_model', $selected_model);

            wp_send_json_success(['message' => 'Success! Connected to OpenAI. Auto-selected model: ' . $selected_model]);
        } else {
            update_option('imaxio_irat_api_connected', 0);
            wp_send_json_error(['message' => 'OpenAI Error: Invalid API Key.']);
        }

    } elseif ($api_provider === 'claude') {
        $url = 'https://api.anthropic.com/v1/models';
        $args = ['headers' => [
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01'
        ]];
        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            update_option('imaxio_irat_api_connected', 0);
            wp_send_json_error(['message' => 'HTTP Error: ' . $response->get_error_message()]);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code === 200) {
            update_option('imaxio_irat_api_connected', 1);

            $selected_model = 'claude-haiku-4-5';
            if (isset($data['data']) && is_array($data['data'])) {
                $available_models = array_column($data['data'], 'id');
                if (!in_array($selected_model, $available_models)) {
                    $haiku_models = array_values(array_filter($available_models, function($id) {
                        return strpos($id, 'haiku') !== false;
                    }));
                    if (!empty($haiku_models)) {
                        rsort($haiku_models);
                        $selected_model = $haiku_models[0];
                    } elseif (!empty($available_models)) {
                        $selected_model = $available_models[0];
                    }
                }
            }
            update_option('imaxio_irat_claude_model', $selected_model);

            wp_send_json_success(['message' => 'Success! Connected to Claude. Auto-selected model: ' . $selected_model]);
        } else {
            update_option('imaxio_irat_api_connected', 0);
            wp_send_json_error(['message' => 'Claude Error: Invalid API Key.']);
        }
    }
}

// -------------------------------------------------------------------
// 6. Media Library Integration
// -------------------------------------------------------------------
add_filter('manage_media_columns', 'imaxio_irat_add_media_columns');
function imaxio_irat_add_media_columns($columns) {
    if (get_option('imaxio_irat_api_connected') == 1) {
        $columns['imaxio_irat_rename'] = 'AI Action';
    }
    return $columns;
}

add_action('manage_media_custom_column', 'imaxio_irat_media_custom_column', 10, 2);
function imaxio_irat_media_custom_column($column_name, $post_id) {
    if ($column_name === 'imaxio_irat_rename') {
        if (wp_attachment_is_image($post_id)) {
            echo '<button type="button" class="button button-primary imaxio-irat-media-btn" data-id="' . esc_attr($post_id) . '">AI Image Rename</button>';
            echo '<span class="imaxio-irat-status" id="imaxio-irat-status-' . esc_attr($post_id) . '" style="display:block; margin-top:5px; font-size:12px;"></span>';
        } else {
            echo '<span class="description">Not an image</span>';
        }
    }
}

add_filter('attachment_fields_to_edit', 'imaxio_irat_attachment_field_btn', 10, 2);
function imaxio_irat_attachment_field_btn($form_fields, $post) {
    if (get_option('imaxio_irat_api_connected') != 1) {
        return $form_fields;
    }
    
    if (wp_attachment_is_image($post->ID)) {
        $form_fields['imaxio_irat_rename_btn'] = [
            'label' => 'AI SEO Optimizer',
            'input' => 'html',
            'html'  => '<button type="button" class="button button-primary imaxio-irat-media-btn" style="width: 100%; text-align: center;" data-id="' . esc_attr($post->ID) . '">AI Image Rename</button>' .
                       '<span class="imaxio-irat-status" id="imaxio-irat-status-' . esc_attr($post->ID) . '" style="display:block; margin-top:5px; font-size:12px; text-align:center;"></span>',
        ];
    }
    return $form_fields;
}

// -------------------------------------------------------------------
// 7. AJAX Endpoint: Process Image, Call AI, and Rename
// -------------------------------------------------------------------
add_action('wp_ajax_imaxio_irat_process_image', 'imaxio_irat_process_image_callback');
function imaxio_irat_process_image_callback() {
    check_ajax_referer('imaxio_irat_process_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
    if (!$post_id) wp_send_json_error(['message' => 'Invalid Image ID.']);

    $file_path = get_attached_file($post_id);
    if (!$file_path || !file_exists($file_path)) {
        wp_send_json_error(['message' => 'Physical file not found on server.']);
    }

    // Secure local file reading using WP_Filesystem
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH . '/wp-admin/includes/file.php';
        WP_Filesystem();
    }
    
    $file_content = $wp_filesystem->get_contents($file_path);
    if (!$file_content) {
        wp_send_json_error(['message' => 'Unable to read the physical file securely via WP_Filesystem.']);
    }

    $mime_type = get_post_mime_type($post_id);
    $api_key = get_option('imaxio_irat_api_key');
    $api_provider = get_option('imaxio_irat_api_provider');
    $scope = get_option('imaxio_irat_project_scope');

    $base64_image = base64_encode($file_content);
    $prompt = "Context: $scope. Analyze this image. Return ONLY a valid JSON object with two keys: 'title' (a short, descriptive, SEO-friendly filename, lowercase, words separated by hyphens, no file extension) and 'alt' (a descriptive SEO alt text for the image, maximum 100 characters). Do NOT wrap the JSON in markdown code blocks. Just return the raw JSON.";

    $response_text = '';

    if ($api_provider === 'gemini') {
        $gemini_model = get_option('imaxio_irat_gemini_model', 'gemini-2.5-flash');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $gemini_model . ':generateContent?key=' . $api_key;
        
        $body = [
            'contents' => [
                ['parts' => [
                    ['text' => $prompt],
                    ['inline_data' => ['mime_type' => $mime_type, 'data' => $base64_image]]
                ]]
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($body),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) wp_send_json_error(['message' => 'API Request failed: ' . $response->get_error_message()]);
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['error'])) wp_send_json_error(['message' => 'AI Error: ' . $data['error']['message']]);
        
        $response_text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

    } elseif ($api_provider === 'openai') {
        $openai_model = get_option('imaxio_irat_openai_model', 'gpt-4o-mini');
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $body = [
            'model' => $openai_model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => "data:$mime_type;base64,$base64_image"]]
                    ]
                ]
            ],
            'response_format' => ['type' => 'json_object'] // Force JSON
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => json_encode($body),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) wp_send_json_error(['message' => 'API Request failed: ' . $response->get_error_message()]);
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['error'])) wp_send_json_error(['message' => 'AI Error: ' . $data['error']['message']]);
        
        $response_text = $data['choices'][0]['message']['content'] ?? '';

    } elseif ($api_provider === 'claude') {
        $claude_model = get_option('imaxio_irat_claude_model', 'claude-haiku-4-5');
        $url = 'https://api.anthropic.com/v1/messages';

        $body = [
            'model' => $claude_model,
            'max_tokens' => 1024,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime_type, 'data' => $base64_image]]
                    ]
                ]
            ]
        ];

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01'
            ],
            'body' => json_encode($body),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) wp_send_json_error(['message' => 'API Request failed: ' . $response->get_error_message()]);

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['error'])) wp_send_json_error(['message' => 'AI Error: ' . $data['error']['message']]);

        if (isset($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $block) {
                if (isset($block['type']) && $block['type'] === 'text') {
                    $response_text = $block['text'];
                    break;
                }
            }
        }
    }

    $response_text = preg_replace('/```json|
```/i', '', $response_text);
    $ai_data = json_decode(trim($response_text), true);

    if (!$ai_data || !isset($ai_data['title']) || !isset($ai_data['alt'])) {
        wp_send_json_error(['message' => 'Analysis failed. The AI did not return a valid format. Raw: ' . esc_html($response_text)]);
    }

    $new_title = sanitize_text_field($ai_data['title']);
    $new_alt = sanitize_text_field($ai_data['alt']);

    update_post_meta($post_id, '_wp_attachment_image_alt', $new_alt);
    wp_update_post([
        'ID' => $post_id,
        'post_title' => $new_title,
        'post_name' => sanitize_title($new_title)
    ]);

    $path_info = pathinfo($file_path);
    $extension = isset($path_info['extension']) ? '.' . $path_info['extension'] : '';
    $base_filename = sanitize_file_name($new_title);
    $new_filename = $base_filename . $extension;
    $new_file_path = $path_info['dirname'] . '/' . $new_filename;

    if (file_exists($new_file_path) && $file_path !== $new_file_path) {
        $timestamp = gmdate('Ymd_His');
        $new_filename = $base_filename . '-' . $timestamp . $extension;
        $new_file_path = $path_info['dirname'] . '/' . $new_filename;
    }

    if ($file_path !== $new_file_path) {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
        if (rename($file_path, $new_file_path)) {
            update_attached_file($post_id, $new_file_path);
        } else {
            wp_send_json_error(['message' => 'Database updated, but physical file renaming failed due to server permissions.']);
        }
    }

    wp_send_json_success([
        'message' => 'Success',
        'new_title' => $new_title,
        'new_alt' => $new_alt,
        'new_url' => wp_get_attachment_url($post_id),
        'new_filename' => wp_basename($new_file_path)
    ]);
}