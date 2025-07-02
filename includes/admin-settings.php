<?php
// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add settings page to WooCommerce.
 */
function wc_sms_notifications_menu() {
    add_submenu_page(
        'woocommerce',
        'SMS Notifications',
        'SMS Notifications',
        'manage_options',
        'wc-sms-notifications',
        'wc_sms_notifications_settings_page'
    );
}

/**
 * Settings page content.
 */
function wc_sms_notifications_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Handle form submission
    if (isset($_POST['wc_sms_notifications_settings_nonce']) && wp_verify_nonce($_POST['wc_sms_notifications_settings_nonce'], 'wc_sms_notifications_settings')) {
        // Sanitize and update options
        $api_key = sanitize_text_field($_POST['xfoo_api_key']);
        $device_id = sanitize_text_field($_POST['xfoo_device_id']);  // Add device ID setting
        $unsubscribe_enable = isset($_POST['wc_sms_notifications_unsubscribe_enable']) ? 'yes' : 'no';  // Unsubscribe option
		$otp_enable = isset($_POST['wc_sms_notifications_otp_enable']) ? 'yes' : 'no';
        $ipinfo_api_key = sanitize_text_field($_POST['ipinfo_api_key']);

        update_option('wc_sms_notifications_api_key', $api_key);
        update_option('wc_sms_notifications_device_id', $device_id);  // Store device ID
        update_option('wc_sms_notifications_new_order_enable', isset($_POST['wc_sms_notifications_new_order_enable']) ? 'yes' : 'no');
        update_option('wc_sms_notifications_processing_order_enable', isset($_POST['wc_sms_notifications_processing_order_enable']) ? 'yes' : 'no');
        update_option('wc_sms_notifications_completed_order_enable', isset($_POST['wc_sms_notifications_completed_order_enable']) ? 'yes' : 'no');
        update_option('wc_sms_notifications_cancelled_order_enable', isset($_POST['wc_sms_notifications_cancelled_order_enable']) ? 'yes' : 'no');
        update_option('wc_sms_notifications_refunded_order_enable', isset($_POST['wc_sms_notifications_refunded_order_enable']) ? 'yes' : 'no');
        update_option('wc_sms_notifications_failed_order_enable', isset($_POST['wc_sms_notifications_failed_order_enable']) ? 'yes' : 'no');
        update_option('wc_sms_notifications_on_hold_order_enable', isset($_POST['wc_sms_notifications_on_hold_order_enable']) ? 'yes' : 'no'); // Add on-hold option
        update_option('wc_sms_notifications_unsubscribe_enable', $unsubscribe_enable);  // Store unsubscribe option
		update_option('wc_sms_notifications_otp_enable', $otp_enable);
        update_option('wc_sms_notifications_ipinfo_api_key', $ipinfo_api_key);


        echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
    }

    // Get existing options
    $api_key = get_option('wc_sms_notifications_api_key', '');
    $device_id = get_option('wc_sms_notifications_device_id', ''); // Get stored device ID
    $new_order_enable = get_option('wc_sms_notifications_new_order_enable', 'no');
    $processing_order_enable = get_option('wc_sms_notifications_processing_order_enable', 'no');
    $completed_order_enable = get_option('wc_sms_notifications_completed_order_enable', 'no');
    $cancelled_order_enable = get_option('wc_sms_notifications_cancelled_order_enable', 'no');
    $refunded_order_enable = get_option('wc_sms_notifications_refunded_order_enable', 'no');
    $failed_order_enable = get_option('wc_sms_notifications_failed_order_enable', 'no');
    $on_hold_order_enable = get_option('wc_sms_notifications_on_hold_order_enable', 'no'); // Get on-hold option
    $unsubscribe_enable = get_option('wc_sms_notifications_unsubscribe_enable', 'no');  // Get unsubscribe option
    $ipinfo_api_key = get_option('wc_sms_notifications_ipinfo_api_key', '');


    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form method="post">
            <?php wp_nonce_field('wc_sms_notifications_settings', 'wc_sms_notifications_settings_nonce'); ?>
   <table class="form-table">
        <tr valign="top">
            <th scope="row"><label for="xfoo_api_key">xfoo.net API Key</label></th>
            <td><input type="text" id="xfoo_api_key" name="xfoo_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text"></td>
        </tr>
        <tr valign="top">
            <th scope="row"><label for="xfoo_device_id">Device ID (Optional)</label></th>
            <td><input type="text" id="xfoo_device_id" name="xfoo_device_id" value="<?php echo esc_attr($device_id); ?>" class="regular-text">
                <p class="description">Leave blank to use the default device.  Specify a device ID to use a specific device for sending SMS.</p>
            </td>
        </tr>
         <tr valign="top">
            <th scope="row"><label for="ipinfo_api_key">IPinfo.io API Key (Optional)</label></th>
            <td><input type="text" id="ipinfo_api_key" name="ipinfo_api_key" value="<?php echo esc_attr($ipinfo_api_key); ?>" class="regular-text">
                <p class="description">Enter your IPinfo.io API key for more accurate country detection.  A default key is used if this is left blank but rate limits may apply.</p>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Enable Notifications</th>
            <td>
                <label><input type="checkbox" name="wc_sms_notifications_new_order_enable" value="yes" <?php checked($new_order_enable, 'yes'); ?>> New Order</label><br>
                <label><input type="checkbox" name="wc_sms_notifications_processing_order_enable" value="yes" <?php checked($processing_order_enable, 'yes'); ?>> Processing Order</label><br>
                <label><input type="checkbox" name="wc_sms_notifications_completed_order_enable" value="yes" <?php checked($completed_order_enable, 'yes'); ?>> Completed Order</label><br>
                <label><input type="checkbox" name="wc_sms_notifications_cancelled_order_enable" value="yes" <?php checked($cancelled_order_enable, 'yes'); ?>> Cancelled Order</label><br>
                <label><input type="checkbox" name="wc_sms_notifications_refunded_order_enable" value="yes" <?php checked($refunded_order_enable, 'yes'); ?>> Refunded Order</label><br>
                <label><input type="checkbox" name="wc_sms_notifications_failed_order_enable" value="yes" <?php checked($failed_order_enable, 'yes'); ?>> Failed Order</label><br>
                <label><input type="checkbox" name="wc_sms_notifications_on_hold_order_enable" value="yes" <?php checked($on_hold_order_enable, 'yes'); ?>> On Hold Order</label><br>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row">Unsubscribe Link</th>
            <td>
                <label><input type="checkbox" name="wc_sms_notifications_unsubscribe_enable" value="yes" <?php checked( $unsubscribe_enable, 'yes' ); ?>> Enable Unsubscribe Link in SMS</label>
                <p class="description">Include an unsubscribe link at the end of each SMS message.</p>
            </td>
        </tr>
		<tr valign="top">
            <th scope="row">OTP Verification</th>
            <td>
                <label><input type="checkbox" name="wc_sms_notifications_otp_enable" value="yes" <?php checked(get_option('wc_sms_notifications_otp_enable'), 'yes'); ?>> Enable OTP for registration</label>
                <p class="description">Require SMS OTP verification during user registration.</p>
            </td>
        </tr>
    </table>
            <?php submit_button(); ?>
        </form>

        <h2>Refresh Devices</h2>
        <button id="refresh-devices-button" class="button">Refresh Devices</button>
        <div id="devices-result"></div>

        <h2>Test SMS</h2>
        <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
            <input type="hidden" name="action" value="wc_sms_notifications_send_test_sms">
            <?php wp_nonce_field('wc_sms_notifications_test_sms', 'wc_sms_notifications_test_sms_nonce'); ?>
            <label for="test_phone_number">Phone Number:</label>
            <input type="text" id="test_phone_number" name="test_phone_number" value="" placeholder="+1XXXXXXXXXX">
            <label for="test_message">Message:</label>
            <input type="text" id="test_message" name="test_message" value="Test SMS from WooCommerce">
            <button type="submit" class="button button-primary">Send Test SMS</button>
        </form>
        <div id="test_sms_result"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#refresh-devices-button').on('click', function() {
            $.ajax({
                url: ajaxurl, // WordPress AJAX URL is already defined
                type: 'POST',
                data: {
                    action: 'wc_sms_notifications_get_devices',
                    nonce: '<?php echo wp_create_nonce("wc_sms_notifications_get_devices_nonce"); ?>'
                },
                beforeSend: function() {
                    $('#devices-result').html('Loading...');
                },
                success: function(response) {
                    if (response.success) {
                        let devicesHtml = '<h3>Available Devices:</h3><ul>';
                        if (response.data.length > 0) {
                            response.data.forEach(function(device) {
                                devicesHtml += '<li><strong>ID:</strong> ' + device.ID + ', <strong>Name:</strong> ' + device.name + '</li>';
                            });
                        } else {
                            devicesHtml += '<li>No devices found.</li>';
                        }
                        devicesHtml += '</ul>';
                        $('#devices-result').html(devicesHtml);
                    } else {
                        $('#devices-result').html('Error: ' + response.data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $('#devices-result').html('AJAX Error: ' + textStatus + ' - ' + errorThrown);
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX handler for sending test SMS
 */
add_action('wp_ajax_wc_sms_notifications_send_test_sms', 'wc_sms_notifications_send_test_sms_callback');

function wc_sms_notifications_send_test_sms_callback() {
    check_ajax_referer('wc_sms_notifications_test_sms', 'wc_sms_notifications_test_sms_nonce');

    $phone_number = sanitize_text_field($_POST['test_phone_number']);
    $message = sanitize_text_field($_POST['test_message']);

    try {
        $result = wc_sms_notifications_send_sms($phone_number, $message); // Use the same function.
        wp_send_json_success($result);
    } catch (Exception $e) {
        wp_send_json_error($e->getMessage());
    }

    wp_die(); // Required for proper AJAX handling
}

// Add AJAX action to retrieve devices
add_action( 'wp_ajax_wc_sms_notifications_get_devices', 'wc_sms_notifications_get_devices_callback' );

function wc_sms_notifications_get_devices_callback() {
    check_ajax_referer( 'wc_sms_notifications_get_devices_nonce', 'nonce' );

    try {
        $devices = getDevices();
        wp_send_json_success( $devices );
    } catch ( Exception $e ) {
        wp_send_json_error( $e->getMessage() );
    }

    wp_die();
}