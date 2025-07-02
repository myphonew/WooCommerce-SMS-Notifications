# WooCommerce-SMS-Notifications
Wordpress Plugin For WooCommerce SMS Notifications lets you use your Android phone as an SMS gateway via the [xfoo.net](https://xfoo.net) API.

=== WooCommerce SMS Notifications ===
Contributors: space  
Tags: woocommerce, sms, order notification, otp, registration, sms gateway, xfoo, android sms  
Requires at least: 5.6  
Tested up to: 6.5  
Requires PHP: 7.2  
Stable tag: 1.0.3  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Use your Android phone as an SMS/MMS gateway for WooCommerce! Send SMS order notifications and OTPs using xfoo.net API.

== Description ==

**WooCommerce SMS Notifications** lets you use your Android phone as an SMS gateway via the [cc.xfoo.net](https://cc.xfoo.net) API.

📦 **Features:**
- Send SMS updates on WooCommerce order status changes.
- OTP verification for new user registration (optional).
- Unsubscribe links in SMS (optional).
- Use specific Android device or default device.
- Admin dashboard with test SMS function.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/woocommerce-sms-notifications/`
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Go to **WooCommerce → SMS Notifications** to configure:
   - Enter your xfoo.net API key and Device ID
   - Enable notification types
   - Optionally enable OTP verification for user registration

== Screenshots ==

1. Plugin settings in WooCommerce → SMS Notifications
2. Test SMS interface
3. OTP verification on registration

== Changelog ==

= 1.0.3 =
* Added admin toggle to enable/disable OTP registration verification.
* Improved phone number formatting and validation.

= 1.0.2 =
* Added unsubscribe feature with opt-out links.
* Minor bug fixes.

= 1.0.0 =
* Initial release.

== Frequently Asked Questions ==

= Do I need a third-party SMS gateway? =
Yes. This plugin uses the [xfoo.net](https://xfoo.net) API which turns your Android phone into an SMS/MMS gateway.

= Can I use this without OTP? =
Yes, OTP verification is optional and can be toggled in settings.

== License ==

This plugin is licensed under the GPLv2 or later.
