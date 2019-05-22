=== ING PSP ===
Tags: ING, PSP, WooCommerce, payment, iDEAL, Bancontact, Bank Tansfer, Credit
Card, Visa, VPay, Mastercard, Maestro
Contributors: ingpsp, gingerpayments
Requires at least: 4.0
Tested up to: 5.1.1
Stable tag: 1.3.9
License: The MIT License (MIT)
License URI: https://opensource.org/licenses/MIT

This is the offical ING plugin for Kassa Compleet, ING ePay and ING Checkout.

== Description ==

**With this official ING plugin you can connect your webshop to the following ING PSP products:** 
* ING Kassa Compleet for the Dutch market
* ING ePay for the Belgium market
* ING Checkout for the international market

By integrating your webshop with ING Payment Service Provider (PSP) you can 
accept payments from your customers in an easy and trusted manner with all 
relevant payment methods supported. 
The following payment methods are supported, depending on which ING PSP product 
you implement: iDEAL, Bancontact, Visa, mastercard, V PAY, Maestro, PayPal, 
SOFORT, Klarna and Bank transfers. 

Via our merchant portal you can easily view all incoming orders, payment status
and revenue at a glance on your PC, mobile phone or tablet.

You will, of course, have quick access to your funds. ING can remit your funds
on your account the next day, weekly or monthly. These and many other options
can be easily configured in your account.

Apply now for your free test account and experience the benefits of KassaCompleet, 
ePay and Checkout within minutes.

**Advantages and Possibilities:**

* Easy integration in your WooCommerce webshop
* Complete overview in the merchant portal
* All main payment methods supported that your customers need
* You decide when to remit your funds
* Customizable dashboard to monitor your business

**Installation & Support:**

Via this plugin you can easily and quickly integrate Kassa Compleet, ePay 
and Checkout in your WooCommerce web shop.

Furthermore, you can download the integration guide from the portal and should
you have any questions, our support desk is there to help you.


== Pre-requisites to install the plug-ins == 

- PHP v5.4 and above
- MySQL v5.4 and above


== Installation ==

1. Upload `ing-psp` directory to the `/wp-content/plugins/`
OR
1. Install .zip archive from the console
2. Activate the plugin through the 'Plugins' menu in WordPress

After that configure the Webhook URL in the ING PSP Merchant portal.
The Webhook URL should look like be:
https://www.example.com/?wc-api=woocommerce_ingpsp

If you visit the URL in your browser you should see the following message:
"Only work to do if the status changed"
3. Go to WooCommerce> Settings > Checkout > ING PSP and set the API key. 
You can copy the API key from your merchant portal. Go to Settings > Webshops 
> select the webshop and in the detail screen you can find the API key.


== Screenshots ==

1. ING PSP Configurations Settings

2. ING PSP iDEAL Payment Method Configuration Options

3. ING PSP Checkout Option


== Frequently Asked Questions ==

= Where do I get ING PSP API key? =

Go to ING PSP Merchant Portal -> Settings -> Webshops -> Trade Name -> API Key

= WooCommerce & qTranslate-X compatibility =

In order for webhook URL to be accessible by ING PSP API you need to go:
Settings -> Languages -> General

And under "URL Modification Mode" check the `"Hide URL language information for
default language."` box.

= Which version of PHP does ING PSP WooCommerce plugin require =
ING PSP WooCommerce plugin works with PHP versions 5.4 or above.

== Changelog ==

= 1.3.9 =
* Fix for DOB check

= 1.3.6 =
* Remove DOB check

= 1.3.5 =
* Use dropdowns for DOB

= 1.3.4 =
* Handle AfterPay testing. Correct calculation of line item totals. Support different billing address.

= 1.3.3 =
* Updated translations. Supporting dutch format for DOB

= 1.3.2 =
* Ads AfterPay

= 1.3.1.2 =
* Updated the logo's of Sofort en Klarna to the new style

= 1.3.1.1 =
* Patched ing-psp

= 1.3.1 =
* Updated ing-php library to version 1.3.4
* Added dynamic order descriptions
* Added WooCommerce 3.x support

= 1.3.0 =
* Updated ing-php library to v1.3.2
* Implemented Payconiq
* Added multilingual support for Payconiq

= 1.2.5 =
* Updated ing-php library to v1.3.1
* Implemented Klarna automatic order capturing
* Some other fixes

= 1.2.4 =
* Added localisation support
* Added German, French and Dutch languages
* Added plugin version information to orders

= 1.2.3 =
* Updated ing-php library to version 1.2.8

= 1.2.2 =
* Updated ing-php library to version 1.2.7

= 1.2 =
* Added PayPal payment method
* Added SOFORT payment method
* Added Klarna payment method
* Added HomePay payment method

= 1.1 =
* Updated ING PSP API bindings library
* Code re-factoring and cleanup

= 1.0 =
* Initial release
