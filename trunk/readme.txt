=== EMS PAY ===
Tags: EMS PAY, WooCommerce, payment
Contributors: emspay, gingerpayments
Requires at least: 4.0
Tested up to: 5.1.1
Stable tag: 1.0.0
License: The MIT License (MIT)
License URI: https://opensource.org/licenses/MIT

This is the offical EMS PAY plugin

== Description ==

Official EMSPay WooCommerce plugin

== Pre-requisites to install the plug-ins == 

- PHP v5.4 and above
- MySQL v5.4 and above

== Installation ==

1. Upload `ems-pay` directory to the `/wp-content/plugins/`
OR
1. Install .zip archive from the console
2. Activate the plugin through the 'Plugins' menu in WordPress

After that configure the Webhook URL in the EMS PAY Merchant portal.
The Webhook URL should look like be:
https://www.example.com/?wc-api=woocommerce_emspay

If you visit the URL in your browser you should see the following message:
"Only work to do if the status changed"
3. Go to WooCommerce> Settings > Checkout > EMS PAY and set the API key. 
You can copy the API key from your merchant portal. Go to Settings > Webshops 
> select the webshop and in the detail screen you can find the API key.


== Screenshots ==

** TODO **

== Frequently Asked Questions ==

** TODO **

== Changelog ==

= 1.0.0 =
* Initial release