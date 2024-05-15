<?php
/*
Plugin Name: Yandex SmartCaptcha
Plugin URI: https://yoursite.com
Description: Integrate Yandex SmartCaptcha into your WordPress site as a replacement for reCAPTCHA and hCaptcha.
Version: 0.70-alpha
Author: Jaspreet Singh
Author URI: https://jaspreet.net
License: GPL2
*/

// Register plugin settings
function yandex_smartcaptcha_register_settings() {
    add_option('yandex_smartcaptcha_replace_recaptcha', false);
    add_option('yandex_smartcaptcha_replace_hcaptcha', false);
    add_option('yandex_smartcaptcha_key', '');
    add_option('yandex_smartcaptcha_secret', '');

    register_setting('yandex_smartcaptcha_options_group', 'yandex_smartcaptcha_replace_recaptcha', 'boolval');
    register_setting('yandex_smartcaptcha_options_group', 'yandex_smartcaptcha_replace_hcaptcha', 'boolval');
    register_setting('yandex_smartcaptcha_options_group', 'yandex_smartcaptcha_key');
    register_setting('yandex_smartcaptcha_options_group', 'yandex_smartcaptcha_secret');
}
add_action('admin_init', 'yandex_smartcaptcha_register_settings');

// Add menu item and page for plugin settings
function yandex_smartcaptcha_add_admin_menu() {
    add_options_page('Yandex SmartCaptcha Settings', 'Yandex SmartCaptcha', 'manage_options', 'yandex_smartcaptcha', 'yandex_smartcaptcha_settings_page');
}
add_action('admin_menu', 'yandex_smartcaptcha_add_admin_menu');

// Render plugin settings page
function yandex_smartcaptcha_settings_page() {
    ?>
    <div class="wrap">
        <h2>Yandex SmartCaptcha Settings</h2>
        <?php
            // Check if Yandex SmartCaptcha script is loaded
            $script_loaded = false;
            if (wp_script_is('yandex-smartcaptcha', 'enqueued')) {
                // Check if the yc object is defined (indicating successful connection to Yandex SmartCaptcha)
                ob_start();
                ?>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        if (typeof yc !== "undefined") {
                            document.getElementById('yandex_smartcaptcha_status').innerText = 'Connected to Yandex SmartCaptcha';
                            document.getElementById('yandex_smartcaptcha_status').classList.add('connected');
                        } else {
                            document.getElementById('yandex_smartcaptcha_status').innerText = 'Script loaded, but not connected to Yandex SmartCaptcha';
                            document.getElementById('yandex_smartcaptcha_status').classList.add('not-connected');
                        }
                    });
                </script>
                <?php
                ob_get_clean();
                $script_loaded = true;
            }
        ?>
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
        <?php if ($script_loaded) : ?>
            <div id="yandex_smartcaptcha_status" class="yandex-smartcaptcha-status"></div>
        <?php endif; ?>
    </div>
    <?php
}

// Enqueue Yandex SmartCaptcha script and styles
function yandex_smartcaptcha_enqueue_assets() {
    $replace_recaptcha = get_option('yandex_smartcaptcha_replace_recaptcha');
    $replace_hcaptcha = get_option('yandex_smartcaptcha_replace_hcaptcha');

    if ($replace_recaptcha || $replace_hcaptcha) {
        wp_enqueue_style('yandex-smartcaptcha-css', plugins_url('yandex-smartcaptcha.css', __FILE__));
        wp_enqueue_script('yandex-smartcaptcha', 'https://captcha.yandex.net/key/', array(), '1.0', true);
        wp_add_inline_script('yandex-smartcaptcha', 'document.addEventListener("DOMContentLoaded", function() { if (typeof yc === "undefined") { console.error("Yandex SmartCaptcha script failed to load. Please check your Yandex SmartCaptcha keys."); } });');
    }
}
add_action('wp_enqueue_scripts', 'yandex_smartcaptcha_enqueue_assets');

// Detect if reCAPTCHA or hCaptcha is already being used
function yandex_smartcaptcha_detect_existing_captcha() {
    ob_start();
    add_action('shutdown', function() {
        $html = ob_get_clean();
        $has_recaptcha = strpos($html, 'https://www.google.com/recaptcha/api.js');
        $has_hcaptcha = strpos($html, 'https://hcaptcha.com/1/api.js');

        if ($has_recaptcha !== false) {
            echo '<div class="notice notice-info"><p>reCAPTCHA is already being used on this site.</p></div>';
        }

        if ($has_hcaptcha !== false) {
            echo '<div class="notice notice-info"><p>hCaptcha is already being used on this site.</p></div>';
        }
    });
}
add_action('admin_notices', 'yandex_smartcaptcha_detect_existing_captcha');

// Shortcode for Contact Form 7 integration
function yandex_smartcaptcha_contact_form_7_shortcode($atts) {
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

    // Enqueue JavaScript for Yandex SmartCaptcha
    wp_enqueue_script('yandex-smartcaptcha');

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
}
add_shortcode('yandex_smartcaptcha_contact_form_7', 'yandex_smartcaptcha_contact_form_7_shortcode');
