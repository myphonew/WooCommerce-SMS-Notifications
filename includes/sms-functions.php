<?php
// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Function to format phone number.  This is a basic example.  You might need to adjust
 * it based on your target countries.  Consider using a library for robust phone number formatting.
 *
 * @param string $phone_number
 * @return string
 */
function wc_sms_notifications_format_phone_number($phone_number) {
    // Remove all non-numeric characters
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);

    // Add a leading '+' if it doesn't exist
    if (strpos($phone_number, '+') !== 0) {
        $phone_number = '+' . $phone_number;
    }

    return $phone_number;
}


/**
 * Send SMS function.
 *
 * @param string $number  Phone number to send to.
 * @param string $message Message to send.
 * @param bool   $include_unsubscribe Whether to include the unsubscribe link. Defaults to true.
 * @return array  The response from the SMS gateway.
 * @throws Exception If there is an error sending the SMS.
 */
function wc_sms_notifications_send_sms($number, $message, $include_unsubscribe = true) {
    $api_key = get_option('wc_sms_notifications_api_key');
    $device_id = get_option('wc_sms_notifications_device_id', 0); // Get device ID, default to 0 if not set
    $unsubscribe_enable = get_option('wc_sms_notifications_unsubscribe_enable', 'no'); // Get unsubscribe setting

    if (empty($api_key)) {
        throw new Exception('API Key is not set. Please configure the plugin settings.');
    }

    $number = wc_sms_notifications_format_phone_number($number);

    // Add unsubscribe link if enabled and allowed
    if ($include_unsubscribe && $unsubscribe_enable === 'yes') {
        $unsubscribe_link = wc_sms_notifications_generate_unsubscribe_link($number);
        $message .= "\n\nUnsubscribe: " . $unsubscribe_link;
    }

    // Use the xfoo.net API to send the SMS.
    try {
        // If a device ID is specified, use it. Otherwise, use the default.
        if (!empty($device_id)) {
            $msg = sendSingleMessage($number, $message, $device_id);
        } else {
            $msg = sendSingleMessage($number, $message);
        }

        return $msg; // Return the API response.

    } catch (Exception $e) {
        // Log the error (optional)
        error_log('SMS Sending Error: ' . $e->getMessage());
        throw new Exception('SMS Sending Failed: ' . $e->getMessage());
    }
}

/**
 * Generate Unsubscribe Link
 *
 * @param string $phone_number
 *
 * @return string
 */
function wc_sms_notifications_generate_unsubscribe_link( $phone_number ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_sms_unsubscribes';

    // Generate a unique unsubscribe key
    $unsubscribe_key = wp_generate_password( 40, false, false );

    // Insert the phone number and unsubscribe key into the database
    $wpdb->insert(
        $table_name,
        array(
            'phone_number'    => $phone_number,
            'unsubscribe_key' => $unsubscribe_key,
            'created_at'      => current_time( 'mysql' ),
        ),
        array(
            '%s', // phone_number
            '%s', // unsubscribe_key
            '%s', // created_at
        )
    );

    // Construct the unsubscribe link
    $unsubscribe_url = home_url( '/sms-unsubscribe/' . $unsubscribe_key );

    return $unsubscribe_url;
}

/**
 * Check if Phone Number is Unsubscribed
 *
 * @param string $phone_number
 * @return bool
 */
function wc_sms_notifications_is_phone_number_unsubscribed( $phone_number ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_sms_unsubscribes';

    $result = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE phone_number = %s",
            $phone_number
        )
    );

    return ( $result > 0 );
}

/**
 * Handles order status changes and sends SMS notifications.
 *
 * @param int    $order_id   The ID of the order.
 * @param string $old_status The old order status.
 * @param string $new_status The new order status.
 */
function wc_sms_notifications_handle_order_status_change($order_id, $old_status, $new_status) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return; // Order not found.
    }

    $billing_phone = $order->get_billing_phone();

    if (empty($billing_phone)) {
        error_log("SMS Notifications: No billing phone number found for order " . $order_id);
        return; // No phone number.
    }

    // Check if the phone number has unsubscribed
     if (wc_sms_notifications_is_phone_number_unsubscribed($billing_phone)) {
        return; // Phone number has unsubscribed, don't send SMS
    }

    // Determine which notifications are enabled and send the SMS accordingly.
    $message = '';

    switch ($new_status) {
        case 'processing':
            if (get_option('wc_sms_notifications_processing_order_enable') == 'yes') {
                $message = "Your order #" . $order_id . " is now being processed.";
            }
            break;
        case 'completed':
            if (get_option('wc_sms_notifications_completed_order_enable') == 'yes') {
                $message = "Your order #" . $order_id . " is now complete.";
            }
            break;
        case 'cancelled':
            if (get_option('wc_sms_notifications_cancelled_order_enable') == 'yes') {
                $message = "Your order #" . $order_id . " has been cancelled.";
            }
            break;
        case 'refunded':
            if (get_option('wc_sms_notifications_refunded_order_enable') == 'yes') {
                $message = "Your order #" . $order_id . " has been refunded.";
            }
            break;
        case 'failed':
            if (get_option('wc_sms_notifications_failed_order_enable') == 'yes') {
                $message = "Your order #" . $order_id . " has failed.";
            }
            break;
        case 'on-hold':  // ADDED:  On Hold status
            if (get_option('wc_sms_notifications_on_hold_order_enable') == 'yes') {
                $message = "Your order #" . $order_id . " is currently on hold. We will contact you shortly.";
            }
            break;
        case 'pending':
           //Do nothing.  No notification for Pending Payment
            break;
        default:
            // Optionally handle other statuses or do nothing.
            break;
    }

    if (!empty($message)) {
        try {
            wc_sms_notifications_send_sms($billing_phone, $message);
        } catch (Exception $e) {
            error_log('SMS Notifications: Error sending SMS for order ' . $order_id . ': ' . $e->getMessage());
        }
    }
}

/**
 * Handles new order and sends SMS notifications.
 *
 * @param int $order_id The ID of the order.
 */
function wc_sms_notifications_handle_new_order( $order_id ) {
    $order = wc_get_order( $order_id );

    if ( !$order ) {
        return; // Order not found.
    }

    $billing_phone = $order->get_billing_phone();

    if ( empty( $billing_phone ) ) {
        error_log( "SMS Notifications: No billing phone number found for order " . $order_id );
        return; // No phone number.
    }

    // Check if the phone number has unsubscribed
    if (wc_sms_notifications_is_phone_number_unsubscribed($billing_phone)) {
        return; // Phone number has unsubscribed, don't send SMS
    }

    if (get_option('wc_sms_notifications_new_order_enable') == 'yes') {
        $message = "Thank you for your order #" . $order_id . ". We will notify you when it is processed.";

        try {
            wc_sms_notifications_send_sms( $billing_phone, $message );
        } catch ( Exception $e ) {
            error_log( 'SMS Notifications: Error sending SMS for order ' . $order_id . ': ' . $e->getMessage() );
        }
    }
}