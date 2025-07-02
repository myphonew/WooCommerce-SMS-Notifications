<?php
// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin activation hook.
 */
function wc_sms_notifications_activate() {
    // Create a custom database table to store unsubscribe keys.
    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_sms_unsubscribes';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        phone_number varchar(255) NOT NULL,
        unsubscribe_key varchar(255) NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Flush rewrite rules after activation (important for the unsubscribe link)
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook.
 */
function wc_sms_notifications_deactivate() {
    // Clean up any data or options if needed.
    // For example, you might want to remove the custom table.
    // However, be careful not to remove user data unless absolutely necessary.

    // Optionally remove the custom table on deactivation:
     global $wpdb;
     $table_name = $wpdb->prefix . 'wc_sms_unsubscribes';
     $sql = "DROP TABLE IF EXISTS $table_name";
     $wpdb->query($sql);
}