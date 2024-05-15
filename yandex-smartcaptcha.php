<?php
/*
Plugin Name: Yandex SmartCaptcha
Plugin URI: https://www.jaspreet.net
Description: Integrate Yandex SmartCaptcha into your WordPress site as a replacement for reCAPTCHA and hCaptcha.
Version: 0.10-ALPHA
Author: Jaspreet Singh
Author URI: https://www.jaspreet.net
License: MIT
*/

// Register plugin settings
add_action('admin_init', function() {
    add_option('yandex_smartcaptcha_replace_recaptcha', false);
    add_option('yandex_smartcaptcha_replace_hcaptcha', false);
    add_option('yandex_smartcaptcha_key', '');
    add_option('yandex_smartcaptcha_secret', '');

    register_setting('yandex_smartcaptcha_options_group', 'yandex_smartcaptcha_replace_recaptcha', 'boolval');
    register_setting('yandex_smartcaptcha_options_group', 'yandex_smartcaptcha_replace_hcaptcha', 'boolval');
    register_setting('yandex_smartcaptcha_options_group', 'yandex_smartcaptcha_key');
    register_setting('yandex_smartcaptcha_options_group', 'yandex_smartcaptcha_secret');
});

// Add menu item and page for plugin settings
add_action('admin_menu', function() {
    add_options_page('Yandex SmartCaptcha Settings', 'Yandex SmartCaptcha', 'manage_options', 'yandex_smartcaptcha', function() {
        // Check Yandex Cloud connectivity and key acceptance
        $status_class = 'error';
        $status_message = 'Not Connected';

        $key = get_option('yandex_smartcaptcha_key');
        $secret = get_option('yandex_smartcaptcha_secret');

        if (!empty($key) && !empty($secret)) {
            $url = 'https://captcha.yandex.net';
            $response = wp_remote_get($url);

            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $status_class = 'success';
                $status_message = 'Connected';
            } else {
                $status_message = 'Unable to connect';
            }
        }

        ?>
        <style>
            .status-indicator {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 4px;
                color: #fff;
            }

            .success {
                background-color: #28a745;
            }

            .error {
                background-color: #dc3545;
            }
        </style>
        <div class="wrap">
            <h2>Yandex SmartCaptcha Settings</h2>
            <div id="yandex_smartcaptcha_status" class="status-indicator <?php echo $status_class; ?>"><?php echo $status_message; ?></div>
            <form method="post" action="options.php">
                <?php settings_fields('yandex_smartcaptcha_options_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Replace reCAPTCHA</th>
                        <td><input type="checkbox" name="yandex_smartcaptcha_replace_recaptcha" value="1" <?php checked(get_option('yandex_smartcaptcha_replace_recaptcha'), true); ?> /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Replace hCaptcha</th>
                        <td><input type="checkbox" name="yandex_smartcaptcha_replace_hcaptcha" value="1" <?php checked(get_option('yandex_smartcaptcha_replace_hcaptcha'), true); ?> /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Yandex SmartCaptcha Key</th>
                        <td><input type="text" name="yandex_smartcaptcha_key" value="<?= esc_attr(get_option('yandex_smartcaptcha_key')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Yandex SmartCaptcha Secret</th>
                        <td><input type="text" name="yandex_smartcaptcha_secret" value="<?= esc_attr(get_option('yandex_smartcaptcha_secret')); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    });
});

// Enqueue Yandex SmartCaptcha script
add_action('wp_enqueue_scripts', function() {
    $replaceRecaptcha = get_option('yandex_smartcaptcha_replace_recaptcha');
    $replaceHcaptcha = get_option('yandex_smartcaptcha_replace_hcaptcha');

    if ($replaceRecaptcha || $replaceHcaptcha) {
        wp_enqueue_script('yandex-smartcaptcha', 'https://captcha.yandex.net/key/', array(), '1.0', true);
    }
});

// Shortcode for Contact Form 7 integration
add_shortcode('yandex_smartcaptcha_contact_form_7', function($atts) {
    $atts = shortcode_atts(array(
        'id' => '',
    ), $atts, 'yandex_smartcaptcha_contact_form_7');

    // Check if Contact Form 7 plugin is activated
    if (!function_exists('wpcf7')) {
        return '<p>Error: Contact Form 7 plugin is not activated.</p>';
    }

    // Check if Contact Form 7 form ID is provided
    if (empty($atts['id'])) {
        return '<p>Error: Please provide a Contact Form 7 form ID.</p>';
    }

    // Generate HTML markup for Yandex SmartCaptcha
    $html = '<div id="yandex_smartcaptcha_' . esc_attr($atts['id']) . '"></div>';

    // Add inline JavaScript to initialize Yandex SmartCaptcha
    ob_start();
    ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof yc !== "undefined") {
                yc.captcha.render({
                    element: 'yandex_smartcaptcha_<?php echo esc_js($atts['id']); ?>',
                    lang: 'en',
                    size: 'invisible',
                    callback: function(token) {
                        var form = document.querySelector('form[id="wpcf7-f<?php echo esc_js($atts['id']); ?>-p<?php echo esc_js($atts['id']); ?>-o1"]');
                        if (form) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'g-recaptcha-response';
                            input.value = token;
                            form.appendChild(input);
                        }
                    }
                });
            } else {
                console.error("Yandex SmartCaptcha script failed to load. Please check your Yandex SmartCaptcha keys.");
            }
        });
    </script>
    <?php
    $javascript = ob_get_clean();

    return $html . $javascript;
});

// Add notice for existing reCAPTCHA and hCaptcha usage
add_action('admin_notices', function() {
    ob_start();
    add_action('shutdown', function() {
        $html = ob_get_clean();
        $hasRecaptcha = strpos($html, 'https://www.google.com/recaptcha/api.js');
        $hasHcaptcha = strpos($html, 'https://hcaptcha.com/1/api.js');

        if ($hasRecaptcha !== false) {
            echo '<div class="notice notice-info"><p>reCAPTCHA is already being used on this site.</p></div>';
        }

        if ($hasHcaptcha !== false) {
            echo '<div class="notice notice-info"><p>hCaptcha is already being used on this site.</p></div>';
        }
    });
});
