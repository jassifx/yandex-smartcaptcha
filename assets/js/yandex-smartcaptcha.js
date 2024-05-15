// JavaScript file for Yandex SmartCaptcha functionality

document.addEventListener("DOMContentLoaded", function() {
    var replaceRecaptcha = <?php echo json_encode(get_option('yandex_smartcaptcha_replace_recaptcha')); ?>;
    var replaceHcaptcha = <?php echo json_encode(get_option('yandex_smartcaptcha_replace_hcaptcha')); ?>;

    if (replaceRecaptcha || replaceHcaptcha) {
        // Load Yandex SmartCaptcha script
        var script = document.createElement('script');
        script.src = 'https://captcha.yandex.net/key/';
        script.async = true;
        script.onload = function() {
            initializeSmartCaptcha();
        };
        document.head.appendChild(script);
    }
});

function initializeSmartCaptcha() {
    // Initialize Yandex SmartCaptcha
    yc.captcha.render({
        element: 'yandex_smartcaptcha', // Replace with the ID of your captcha element
        lang: 'en', // Replace with your preferred language code
        size: 'invisible', // Change to 'invisible' for invisible captcha or 'normal' for visible captcha
        callback: function(token) {
            // Callback function to handle token
            // You can perform further actions here, such as submitting the form
            console.log('Yandex SmartCaptcha token:', token);
        }
    });
}
