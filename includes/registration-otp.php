<?php
// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

   // Enqueue JS for OTP only if OTP is enabled
   add_action('wp_enqueue_scripts', 'wc_sms_otp_enqueue_scripts');
   function wc_sms_otp_enqueue_scripts() {
       if (get_option('wc_sms_notifications_otp_enable') !== 'yes') return;

       // Enqueue intl-tel-input CSS
       wp_enqueue_style(
           'intl-tel-input',
           'https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.3/build/css/intlTelInput.css'
       );

       // Enqueue intl-tel-input JS
       wp_enqueue_script(
           'intl-tel-input',  // Handle for the library.  Crucially important!
           'https://cdn.jsdelivr.net/npm/intl-tel-input@17.0.3/build/js/intlTelInput.min.js', // Corrected URL
           [], // No dependencies for the core library
           null,
           true
       );


       wp_enqueue_script(
           'wc-sms-phone-handler',
           plugin_dir_url(__FILE__) . '../assets/js/phone-handler.js',
           ['jquery', 'intl-tel-input'], //  Make intl-tel-input a dependency!
           null,
           true
       );

       wp_localize_script('wc-sms-phone-handler', 'wc_sms_ajax', [
           'ajax_url' => admin_url('admin-ajax.php'),
           'nonce'    => wp_create_nonce('wc_sms_otp_nonce')
       ]);
   }

// Add phone & OTP fields to registration form
add_action('woocommerce_register_form_start', 'wc_sms_add_otp_fields');
function wc_sms_add_otp_fields() {
    if (get_option('wc_sms_notifications_otp_enable') !== 'yes') return;

    ?>
    <p class="form-row form-row-wide">
        <label for="reg_billing_phone"><?php esc_html_e('Phone Number', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
        <input type="tel" class="input-text" name="billing_phone" id="reg_billing_phone" required />
        <input type="hidden" name="billing_country_code" id="reg_billing_country_code" />
        <button type="button" id="send_otp_button"><?php esc_html_e('Send OTP', 'woocommerce'); ?></button>
        <span id="otp_status" style="display:block; margin-top: 5px;"></span>
    </p>
    <p class="form-row form-row-wide">
        <label for="otp_code"><?php esc_html_e('Enter OTP', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
        <input type="text" class="input-text" name="otp_code" id="otp_code" required />
    </p>
    <?php
}

// Save phone number on registration
add_action('woocommerce_created_customer', 'wc_sms_save_billing_phone');
function wc_sms_save_billing_phone($customer_id) {
    if (isset($_POST['billing_phone'])) {
        update_user_meta($customer_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
    }
}

// Validate OTP before registration completes
add_action('woocommerce_register_post', 'wc_sms_validate_otp', 10, 3);
function wc_sms_validate_otp($username, $email, $validation_errors) {
    if (get_option('wc_sms_notifications_otp_enable') !== 'yes') return;

    if (empty($_POST['otp_code']) || empty($_POST['billing_phone'])) {
        $validation_errors->add('otp_missing', __('Please enter your phone number and OTP code.', 'woocommerce'));
        return;
    }

    $submitted_otp = sanitize_text_field($_POST['otp_code']);
    $phone_number  = sanitize_text_field($_POST['billing_phone']);

    $stored_otp = get_transient('wc_sms_otp_' . $phone_number);

    if (!$stored_otp || $submitted_otp !== $stored_otp) {
        $validation_errors->add('otp_invalid', __('Invalid or expired OTP code.', 'woocommerce'));
    }
}

// Handle AJAX OTP request
add_action('wp_ajax_send_otp_sms', 'wc_sms_send_otp_sms');
add_action('wp_ajax_nopriv_send_otp_sms', 'wc_sms_send_otp_sms');
function wc_sms_send_otp_sms() {
    if (get_option('wc_sms_notifications_otp_enable') !== 'yes') {
        wp_send_json_error('OTP is currently disabled.');
    }

    check_ajax_referer('wc_sms_otp_nonce', 'security');

    $phone = sanitize_text_field($_POST['phone']);
    $country_code = sanitize_text_field($_POST['country_code']);
    $full_number = '+' . preg_replace('/\D/', '', $country_code . $phone);

    $otp = rand(100000, 999999);
    set_transient('wc_sms_otp_' . $full_number, $otp, 5 * MINUTE_IN_SECONDS);

    try {
        $message = "Your OTP code is: {$otp}";
        wc_sms_notifications_send_sms($full_number, $message, false);
        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }

    wp_die();
}