<?php
/**
 * Plugin Name: AI Image Rename
 * Description: Optimize your image SEO (Filenames and Alt texts) via AI (OpenAI or Google Gemini) directly from the WordPress media library in one click.
 * Version: 1.0
 * Author: GuilhemF
 * Author URI: https://guilhemf.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Security: prevent direct access
}

// -------------------------------------------------------------------
// 1. Add menu under Settings
// -------------------------------------------------------------------
add_action('admin_menu', 'ai_ir_add_admin_menu');
function ai_ir_add_admin_menu() {
    add_options_page(
        'AI Image Rename',          // Page title
        'AI Image Rename',          // Menu title
        'manage_options',           // Capability
        'ai-image-rename',          // Slug
        'ai_ir_options_page_html'   // Callback function
    );
}

// -------------------------------------------------------------------
// 2. Register settings
// -------------------------------------------------------------------
add_action('admin_init', 'ai_ir_settings_init');
function ai_ir_settings_init() {
    register_setting('ai_ir_plugin_options', 'ai_ir_api_provider');
    register_setting('ai_ir_plugin_options', 'ai_ir_api_key');
    register_setting('ai_ir_plugin_options', 'ai_ir_project_scope');

    add_settings_section(
        'ai_ir_plugin_main_section',
        'API and Context Settings',
        '__return_empty_string',
        'ai_ir_plugin'
    );

    add_settings_field(
        'ai_ir_api_provider_field',
        'AI Provider',
        'ai_ir_api_provider_render',
        'ai_ir_plugin',
        'ai_ir_plugin_main_section'
    );

    add_settings_field(
        'ai_ir_api_key_field',
        'API Key',
        'ai_ir_api_key_render',
        'ai_ir_plugin',
        'ai_ir_plugin_main_section'
    );

    add_settings_field(
        'ai_ir_project_scope_field',
        'Project Description (Scope)',
        'ai_ir_project_scope_render',
        'ai_ir_plugin',
        'ai_ir_plugin_main_section'
    );
}

function ai_ir_api_provider_render() {
    $provider = get_option('ai_ir_api_provider', 'gemini');
    ?>
    <select name="ai_ir_api_provider" id="ai_ir_api_provider">
        <option value="gemini" <?php selected($provider, 'gemini'); ?>>Google Gemini</option>
        <option value="openai" <?php selected($provider, 'openai'); ?>>OpenAI (ChatGPT)</option>
    </select>
    <?php
}

function ai_ir_api_key_render() {
    $apiKey = get_option('ai_ir_api_key');
    echo "<input type='password' name='ai_ir_api_key' id='ai_ir_api_key' value='" . esc_attr($apiKey) . "' style='width: 100%; max-width: 400px;' placeholder='Enter your API key...' />";
}

function ai_ir_project_scope_render() {
    $scope = get_option('ai_ir_project_scope');
    echo "<textarea name='ai_ir_project_scope' rows='5' style='width: 100%; max-width: 400px;' placeholder='Ex: Context: Niche perfume e-shop. Target: Young audience...'>" . esc_textarea($scope) . "</textarea>";
    echo "<p class='description'>This text provides context to the AI for generating SEO-friendly titles and alt tags.</p>";
}

// -------------------------------------------------------------------
// 3. Display Settings Page (UI + JS)
// -------------------------------------------------------------------
function ai_ir_options_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h2>AI Image Rename</h2>
        <form action="options.php" method="post" id="ai-ir-settings-form">
            <?php
            settings_fields('ai_ir_plugin_options');
            do_settings_sections('ai_ir_plugin');
            submit_button('Save Settings');
            ?>
        </form>

        <hr style="margin: 30px 0;">
        
        <h3>API Connection Test</h3>
        <p>Verify that your API key is valid before using the media library.</p>
        <button type="button" id="ai-ir-test-btn" class="button button-secondary">Test API Connection</button>
        <span id="ai-ir-test-result" style="margin-left: 10px; font-weight: bold;"></span>

        <script>
        document.getElementById('ai-ir-test-btn').addEventListener('click', function() {
            const btn = this;
            const resultSpan = document.getElementById('ai-ir-test-result');
            const apiKey = document.getElementById('ai_ir_api_key').value;
            const apiProvider = document.getElementById('ai_ir_api_provider').value;

            if (!apiKey) {
                resultSpan.innerHTML = '<span style="color:#d63638;">Please enter your API key first.</span>';
                return;
            }

            btn.disabled = true;
            resultSpan.innerHTML = 'Testing connection...';

            const formData = new FormData();
            formData.append('action', 'ai_ir_test_api');
            formData.append('api_key', apiKey);
            formData.append('api_provider', apiProvider);
            formData.append('nonce', '<?php echo wp_create_nonce('ai_ir_test_nonce'); ?>'); // Added CSRF Nonce

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                if(data.success) {
                    resultSpan.innerHTML = '<span style="color:#00a32a;">' + data.data.message + '</span>';
                } else {
                    resultSpan.innerHTML = '<span style="color:#d63638;">' + data.data.message + '</span>';
                }
            })
            .catch(error => {
                btn.disabled = false;
                resultSpan.innerHTML = '<span style="color:#d63638;">Server communication error.</span>';
            });
        });
        </script>

        <?php //Debug info ?>
        <?php /* ?>
        <hr style="margin: 40px 0 20px 0;">
        <div style="background: #f0f0f1; border: 1px solid #c3c4c7; padding: 15px; max-width: 600px;">
            <h3 style="margin-top: 0;">🛠️ Debug: Database Values</h3>
            <table class="form-table" role="presentation" style="margin-top: 0;">
                <tbody>
                    <tr>
                        <th scope="row" style="padding: 10px 0;"><strong>Active Provider</strong></th>
                        <td style="padding: 10px 0;"><code><?php echo esc_html(get_option('ai_ir_api_provider', 'Not set')); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row" style="padding: 10px 0;"><strong>Connection Status</strong></th>
                        <td style="padding: 10px 0;"><code><?php echo esc_html(get_option('ai_ir_api_connected', '0')); ?></code> <em>(1 = Connected, 0 = Disconnected)</em></td>
                    </tr>
                    <tr>
                        <th scope="row" style="padding: 10px 0;"><strong>Saved OpenAI Model</strong></th>
                        <td style="padding: 10px 0;"><code><?php echo esc_html(get_option('ai_ir_openai_model', 'Not set')); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row" style="padding: 10px 0;"><strong>Saved Gemini Model</strong></th>
                        <td style="padding: 10px 0;"><code><?php echo esc_html(get_option('ai_ir_gemini_model', 'Not set')); ?></code></td>
                    </tr>
                    <tr>
                        <th scope="row" style="padding: 10px 0;"><strong>API Key Length</strong></th>
                        <td style="padding: 10px 0;">
                            <?php 
                            $key = get_option('ai_ir_api_key', '');
                            echo $key ? '<code>' . strlen($key) . ' characters</code> (Hidden for security)' : '<code>Empty</code>'; 
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        */  ?>

    </div>
    <?php
}

// -------------------------------------------------------------------
// 4. AJAX Endpoint for testing (Real Validation + Save Status)
// -------------------------------------------------------------------
add_action('wp_ajax_ai_ir_test_api', 'ai_ir_test_api_callback');
function ai_ir_test_api_callback() {
    // Check CSRF nonce for security
    check_ajax_referer('ai_ir_test_nonce', 'nonce');

    $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
    $api_provider = isset($_POST['api_provider']) ? sanitize_text_field($_POST['api_provider']) : 'gemini';
    
    if (empty($api_key)) {
        wp_send_json_error(['message' => 'API Key is missing.']);
    }

    if ($api_provider === 'gemini') {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key;
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            update_option('ai_ir_api_connected', 0);
            wp_send_json_error(['message' => 'HTTP Error: ' . $response->get_error_message()]);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code === 200) {
            update_option('ai_ir_api_connected', 1);

            // Dynamic model detection for Gemini
            $selected_model = 'gemini-2.5-flash'; // Fallback
            if (isset($data['models']) && is_array($data['models'])) {
                $flash_models = [];
                foreach ($data['models'] as $model) {
                    // Check if name contains 'flash' and supports 'generateContent'
                    if (strpos($model['name'], 'flash') !== false && isset($model['supportedGenerationMethods']) && in_array('generateContent', $model['supportedGenerationMethods'])) {
                        $flash_models[] = str_replace('models/', '', $model['name']);
                    }
                }
                if (!empty($flash_models)) {
                    rsort($flash_models); // Reverse sort to get the highest version number
                    $selected_model = $flash_models[0];
                }
            }
            update_option('ai_ir_gemini_model', $selected_model);

            wp_send_json_success(['message' => 'Success! Connected to Gemini. Auto-selected model: ' . $selected_model]);
        } else {
            update_option('ai_ir_api_connected', 0);
            wp_send_json_error(['message' => 'Gemini Error: Invalid API Key or request.']);
        }

    } elseif ($api_provider === 'openai') {
        $url = 'https://api.openai.com/v1/models';
        $args = ['headers' => ['Authorization' => 'Bearer ' . $api_key]];
        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            update_option('ai_ir_api_connected', 0);
            wp_send_json_error(['message' => 'HTTP Error: ' . $response->get_error_message()]);
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code === 200) {
            update_option('ai_ir_api_connected', 1);

            // Dynamic model detection for OpenAI
            $selected_model = 'gpt-4o-mini'; // Fallback
            if (isset($data['data']) && is_array($data['data'])) {
                $available_models = array_column($data['data'], 'id');
                if (in_array('gpt-4o-mini', $available_models)) {
                    $selected_model = 'gpt-4o-mini';
                } elseif (in_array('gpt-4o', $available_models)) {
                    $selected_model = 'gpt-4o';
                }
            }
            update_option('ai_ir_openai_model', $selected_model);

            wp_send_json_success(['message' => 'Success! Connected to OpenAI. Auto-selected model: ' . $selected_model]);
        } else {
            update_option('ai_ir_api_connected', 0);
            wp_send_json_error(['message' => 'OpenAI Error: Invalid API Key.']);
        }
    }
}

// -------------------------------------------------------------------
// 5. Media Library Integration (Buttons display)
// -------------------------------------------------------------------

// A. List Mode: Add a column with the blue button
add_filter('manage_media_columns', 'ai_ir_add_media_columns');
function ai_ir_add_media_columns($columns) {
    if (get_option('ai_ir_api_connected') == 1) {
        $columns['ai_ir_rename'] = 'AI Action';
    }
    return $columns;
}

add_action('manage_media_custom_column', 'ai_ir_media_custom_column', 10, 2);
function ai_ir_media_custom_column($column_name, $post_id) {
    if ($column_name === 'ai_ir_rename') {
        // Only for images
        if (wp_attachment_is_image($post_id)) {
            echo '<button type="button" class="button button-primary ai-ir-media-btn" data-id="' . esc_attr($post_id) . '">AI Image Rename</button>';
            echo '<span class="ai-ir-status" id="ai-ir-status-' . esc_attr($post_id) . '" style="display:block; margin-top:5px; font-size:12px;"></span>';
        } else {
            echo '<span class="description">Not an image</span>';
        }
    }
}

// B. Grid/Preview Mode: Add button to the attachment details sidebar
add_filter('attachment_fields_to_edit', 'ai_ir_attachment_field_btn', 10, 2);
function ai_ir_attachment_field_btn($form_fields, $post) {
    if (get_option('ai_ir_api_connected') != 1) {
        return $form_fields;
    }
    
    // Only for images
    if (wp_attachment_is_image($post->ID)) {
        $form_fields['ai_ir_rename_btn'] = [
            'label' => 'AI SEO Optimizer',
            'input' => 'html',
            'html'  => '<button type="button" class="button button-primary ai-ir-media-btn" style="width: 100%; text-align: center;" data-id="' . esc_attr($post->ID) . '">AI Image Rename</button>' .
                       '<span class="ai-ir-status" id="ai-ir-status-' . esc_attr($post->ID) . '" style="display:block; margin-top:5px; font-size:12px; text-align:center;"></span>',
        ];
    }
    return $form_fields;
}

// -------------------------------------------------------------------
// 6. Media Library JavaScript (Process Click)
// -------------------------------------------------------------------
add_action('admin_footer', 'ai_ir_media_library_js');
function ai_ir_media_library_js() {
    if (get_option('ai_ir_api_connected') != 1) return;
    ?>
    <script>
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('ai-ir-media-btn')) {
            e.preventDefault();
            const btn = e.target;
            const postId = btn.getAttribute('data-id');
            const statusSpan = document.getElementById('ai-ir-status-' + postId);
            
            btn.disabled = true;
            statusSpan.style.color = '#2271b1';
            statusSpan.innerText = 'Analyzing image...';

            const formData = new FormData();
            formData.append('action', 'ai_ir_process_image');
            formData.append('post_id', postId);
            formData.append('nonce', '<?php echo wp_create_nonce('ai_ir_process_nonce'); ?>');

            fetch(ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                if(data.success) {
                    statusSpan.style.color = '#00a32a';
                    statusSpan.innerText = 'Success! File & Meta updated.';
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
    });
    </script>
    <?php
}

// -------------------------------------------------------------------
// 7. AJAX Endpoint: Process Image, Call AI, and Rename
// -------------------------------------------------------------------
add_action('wp_ajax_ai_ir_process_image', 'ai_ir_process_image_callback');
function ai_ir_process_image_callback() {
    check_ajax_referer('ai_ir_process_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) wp_send_json_error(['message' => 'Invalid Image ID.']);

    $file_path = get_attached_file($post_id);
    if (!$file_path || !file_exists($file_path)) {
        wp_send_json_error(['message' => 'Physical file not found on server.']);
    }

    $mime_type = get_post_mime_type($post_id);
    $api_key = get_option('ai_ir_api_key');
    $api_provider = get_option('ai_ir_api_provider');
    $scope = get_option('ai_ir_project_scope');

    // 1. Encode image to Base64
    $base64_image = base64_encode(file_get_contents($file_path));

    // 2. Prepare Prompt
    $prompt = "Context: $scope. Analyze this image. Return ONLY a valid JSON object with two keys: 'title' (a short, descriptive, SEO-friendly filename, lowercase, words separated by hyphens, no file extension) and 'alt' (a descriptive SEO alt text for the image, maximum 100 characters). Do NOT wrap the JSON in markdown code blocks. Just return the raw JSON.";

    $response_text = '';

    // 3. Call AI API
    if ($api_provider === 'gemini') {
        // Retrieve dynamically saved model or use fallback
        $gemini_model = get_option('ai_ir_gemini_model', 'gemini-2.5-flash');
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
        // Retrieve dynamically saved model or use fallback
        $openai_model = get_option('ai_ir_openai_model', 'gpt-4o-mini');
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
    }

    // 4. Parse AI JSON
    $response_text = preg_replace('/```json|```/i', '', $response_text);
    $ai_data = json_decode(trim($response_text), true);

    if (!$ai_data || !isset($ai_data['title']) || !isset($ai_data['alt'])) {
        wp_send_json_error(['message' => 'Analysis failed. The AI did not return a valid format. Raw: ' . esc_html($response_text)]);
    }

    $new_title = sanitize_text_field($ai_data['title']);
    $new_alt = sanitize_text_field($ai_data['alt']);

    // 5. Update Database (Title, Slug, Alt)
    update_post_meta($post_id, '_wp_attachment_image_alt', $new_alt);
    wp_update_post([
        'ID' => $post_id,
        'post_title' => $new_title,
        'post_name' => sanitize_title($new_title)
    ]);

    // 6. Rename Physical File
    $path_info = pathinfo($file_path);
    $extension = isset($path_info['extension']) ? '.' . $path_info['extension'] : '';
    $base_filename = sanitize_file_name($new_title);
    $new_filename = $base_filename . $extension;
    $new_file_path = $path_info['dirname'] . '/' . $new_filename;

    // Duplication Check
    if (file_exists($new_file_path) && $file_path !== $new_file_path) {
        $timestamp = date('Ymd_His');
        $new_filename = $base_filename . '-' . $timestamp . $extension;
        $new_file_path = $path_info['dirname'] . '/' . $new_filename;
    }

    if ($file_path !== $new_file_path) {
        if (rename($file_path, $new_file_path)) {
            update_attached_file($post_id, $new_file_path);
        } else {
            wp_send_json_error(['message' => 'Database updated, but physical file renaming failed due to server permissions.']);
        }
    }

    wp_send_json_success(['message' => 'Success']);
}