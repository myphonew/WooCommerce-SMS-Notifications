<?php
/**
 * Plugin Name: WooCommerce SMS Notifications
 * Description: Sends SMS notifications for WooCommerce order status updates using xfoo.net API.
 * Version: 1.0.3
 * Author: SpaceMPW
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Define constants. Consider moving these to options.
define("XFOO_SERVER", "https://cc.xfoo.net");
define("XFOO_API_KEY", get_option('wc_sms_notifications_api_key'));
define("XFOO_USE_SPECIFIED", 0);
define("XFOO_USE_ALL_DEVICES", 1);
define("XFOO_USE_ALL_SIMS", 2);

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/activation.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/sms-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/registration-otp.php';
require_once plugin_dir_path(__FILE__) . 'includes/xfoo-api.php';

// Activation/Deactivation hooks (moved to includes/activation.php)
register_activation_hook(__FILE__, 'wc_sms_notifications_activate');
register_deactivation_hook(__FILE__, 'wc_sms_notifications_deactivate');


// Add settings page to WooCommerce (moved to includes/admin-settings.php)
add_action('admin_menu', 'wc_sms_notifications_menu');

// WooCommerce Order Status Change Hook
add_action('woocommerce_order_status_changed', 'wc_sms_notifications_order_status_changed', 10, 3);

// WooCommerce New Order Hook
add_action('woocommerce_new_order', 'wc_sms_notifications_new_order', 10, 1);

/**
 * WooCommerce Order Status Change Hook.
 */
function wc_sms_notifications_order_status_changed($order_id, $old_status, $new_status) {
    wc_sms_notifications_handle_order_status_change($order_id, $old_status, $new_status);
}

/**
 * WooCommerce New Order Hook.
 */
function wc_sms_notifications_new_order( $order_id ) {
    wc_sms_notifications_handle_new_order( $order_id );
}

// Add unsubscribe endpoint
add_action( 'init', 'wc_sms_notifications_add_rewrite_rule' );
add_filter( 'query_vars', 'wc_sms_notifications_add_query_vars' );
add_action( 'template_redirect', 'wc_sms_notifications_handle_unsubscribe' );

/**
 * Add Rewrite Rule
 */
function wc_sms_notifications_add_rewrite_rule() {
    add_rewrite_rule( '^sms-unsubscribe/([^/]*)/?', 'index.php?sms_unsubscribe=$matches[1]', 'top' );
    // flush_rewrite_rules(); //Important:  Run this ONCE after adding the rule.  Then comment out.
}

/**
 * Add Query Vars
 */
function wc_sms_notifications_add_query_vars( $vars ) {
    $vars[] = 'sms_unsubscribe';
    return $vars;
}

/**
 * Handle Unsubscribe Request
 */
function wc_sms_notifications_handle_unsubscribe() {
    global $wp_query;

    if ( isset( $wp_query->query_vars['sms_unsubscribe'] ) ) {
        $unsubscribe_key = sanitize_text_field( $wp_query->query_vars['sms_unsubscribe'] );

        // Verify and process unsubscribe
        $unsubscribed = wc_sms_notifications_process_unsubscribe( $unsubscribe_key );

        // Display a message to the user
        if ( $unsubscribed ) {
            echo '<div class="woocommerce-message">You have been successfully unsubscribed from SMS notifications.</div>';
        } else {
            echo '<div class="woocommerce-error">Invalid unsubscribe request.</div>';
        }

        exit; // Stop further page loading
    }
}

/**
 * Process Unsubscribe
 *
 * @param string $unsubscribe_key
 *
 * @return bool
 */
function wc_sms_notifications_process_unsubscribe( $unsubscribe_key ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_sms_unsubscribes';

    // Query the database for the unsubscribe key
    $result = $wpdb->get_row( $wpdb->prepare( "SELECT phone_number FROM $table_name WHERE unsubscribe_key = %s", $unsubscribe_key ) );

    if ( $result ) {
        $phone_number = $result->phone_number;

        // Delete the unsubscribe key from the database **BEFORE** updating user meta.
        $wpdb->delete( $table_name, array( 'unsubscribe_key' => $unsubscribe_key ) );

        return true;
    }

    return false;
}
