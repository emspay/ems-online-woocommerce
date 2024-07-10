=== EMS Online ===
Tags: EMS Online, WooCommerce, payment
Contributors: emspay, gingerpayments
Requires at least: 5.6
Tested up to: 6.5
Stable tag: 1.3.18
License: The MIT License (MIT)
License URI: https://opensource.org/licenses/MIT

This is the offical EMS Online plugin

== Description ==

This is the offical EMS Online plugin.

EMS helps entrepreneurs with the best, smartest and most efficient payment systems. Both
in your physical store and online in your webshop. With a wide range of payment methods
you can serve every customer.

Why EMS?

Via the EMS website you can create a free test account online 24/7 and try out the online
payment solution. EMS's online solution also offers the option of sending payment links and
accepting QR payments.

The ideal online payment page for your webshop:
- Free test account - available online 24/7
- Wide range of payment methods
- Easy integration via a plug-in or API
- Free shopping cart plug-ins
- Payment page in the look & feel of your webshop
- Reports in the formats CAMT.053, MT940S, MT940 & CODA
- One clear dashboard for all your payment, turnover data and administration functions

Promotion promotion extended!

Choose the EMS Online Payment Solution now
and pay no subscription costs at € 9.95 until June 2021!

Start immediately with your test account
Request it https://portal.emspay.eu/create-test-account?language=NL_NL

Satisfied after testing?
Click on the yellow button [Begin→]
 in the test portal and
simply request your live account.

== Pre-requisites to install the plug-ins ==

- PHP v5.4 and above
- MySQL v5.4 and above

== Installation ==

Manual installation of the EMS WooCommerce plugin using (s)FTP

1. Upload the folder 'ems-online' in the ZIP file into the 'wp-content/plugins' folder of your WordPress installation.
You can use an sFTP or SCP program, for example, to upload the files. There are various sFTP clients that you can download free of charge from the internet, such as WinSCP or Filezilla.
2. Activate the EMS Online plugin in ‘Plugins’ > Installed Plugins.
3. Select ‘WooCommerce’ > ‘Settings’ > Payments and click on EMS Online (Enabled).
4. Configure the EMS Online module ('Manage' button)
- Copy the API key
- Are you offering Klarna on your pay page? In that case enter the following fields:
	- Test API key field. Copy the API Key of your test webshop in the Test API key field.
	When your Klarna application is approved an extra test webshop was created for you to use in your test with Klarna. The name of this webshop starts with ‘TEST Klarna’.
	- Klarna IP
	For the payment method Klarna you can choose to offer it only to a limited set of whitelisted IP addresses. You can use this for instance when you are in the testing phase and want to make sure that Klarna is not available yet for your customers.
	If you do not offer Klarna you can leave the Test API key and Klarna debug IP fields empty.
- Are you offering Afterpay on your pay page?
	- To do this click on the “Manage” button of EMS Online: AfterPay in the payment method overview.
	- Next, see the instructions for Klarna
- Select your preferred Failed payment page. This setting determines the page to which your customer is redirected after a payment attempt has failed. You can choose between the Checkout page (the page where you can choose a payment method) or the Shopping cart page (the page before checkout where the content of the shopping cart is displayed).
- Enable the cURL CA bundle option.
This fixes a cURL SSL Certificate issue that appears in some web-hosting environments where you do not have access to the PHP.ini file and therefore are not able to update server certificates.
- Only for AfterPay payment: To allow AfterPay to be used for any other country just add its country code (in ISO 2 standard) to the "Countries available for AfterPay" field. Example: BE, NL, FR
- Each payment method has a Allowed currencies(settlement) setting with which it works. Depending on this setting, the selected store currency and the allowed currencies for the EMS gateway, payment methods will be filtered on the Checkout page. This setting can be edited for each payment method, if some currencies are not added, but the payment method works with it.
5. Configure each payment method you would like to offer in your webshop.
Enable only those payment methods that you applied for and for which you have received a confirmation from us.
- To configure iDEAL do the following:
	- Go to ‘WooCommerce’ > ‘Settings’ > Payments > ‘EMS Online: iDEAL’.
	- Select Enable iDEAL Payment to include the payment method in your pay page.
- Follow the same procedure for all other payment methods you have enabled.

Manual installation by uploading ZIP file from WordPress administration environment

1. Go to your WordPress admin environment. Upload the ZIP file to your WordPress installation by clicking on ‘Plugins’ > ‘Add New’. No files are overwritten.
2. Select ´Upload plugin´.
3. Select the ems-online.zip file.
4. Continue with step 3 of Installation using (s)FTP.

Compatibility: WordPress 5.6 or higher


== Screenshots ==

1. Checkout page: EMS payment methods

== Frequently Asked Questions ==

= I can't install the plugin =

Please temporarily enable the [WordPress Debug Mode](https://codex.wordpress.org/Debugging_in_WordPress). Edit your `wp-config.php` and set the constants `WP_DEBUG` and `WP_DEBUG_LOG` to `true` and try
it again. When the plugin triggers an error, WordPress will log the error to the log file `/wp-content/debug.log`. Please check this file for errors. When done, don't forget to turn off
the WordPress debug mode by setting the two constants `WP_DEBUG` and `WP_DEBUG_LOG` back to `false`.

= I get a white screen =

Most of the time a white screen means a PHP error. Because PHP won't show error messages on default for security reasons, the page is white. Please turn on the WordPress Debug Mode to turn on PHP error messages (see previous answer).

= I have a different question =

Please contact us via the above "support" tab and add a ticket: please describe your problem as detailed as possible. Include screenshots where appropriate.
Where possible, also include the log file. You can find the log files in `/wp-content/uploads/wc-logs/` or `/wp-content/plugin/woocommerce/logs`.

* Contact EMS Support

Visit the FAQ:
https://developer.emspay.eu/faq

Contact information:
https://developer.emspay.eu/contact

== Changelog ==


** 1.0.0 **

* Initial version

** 1.0.8 **

* Fix Captured and shipped functionality

** 1.0.9 **

* Move files from trunk folder to root folder

** 1.0.10 **

* Fixed sending peyment information in the New Order email for all gateways

** 1.0.11 **

* Add Refund Order functionality

** 1.0.12 **

* Captured transaction status check changed to has-captures flag in accordance with the new behavior of the capture

** 1.0.13 **

* Changed redirect in KP Later (after Order create in the EMS) to 'payment_url'

** 1.0.14 **

* Fixed refund order functionality

** 1.0.15 **

* Adding refund admin description
* Removing empty values before sending data to the API

** 1.1.0 **

* Added the ability for AfterPay to be available in the selected countries.
* Removing Klarna Pay Later fields gender and birthday from checkout form and customer object
* Replaced locally stored ginger-php library on composer library installer.

** 1.1.1 **

* Removed the Webhook option
* Updated EMS Online plugin description in README

** 1.1.2 **

* Expanded updating of order statuses in the store

** 1.1.3 **

* Fixed shipping tax rate functionality

** 1.1.4 **

* Appended processing order post status for correct status update to 'complemented' by WooCommerce
  Updated README

** 1.1.5 **

* Changes regarding to WordPress requirements for placing a plugin on the WordPress store

** 1.1.6 **

* Updated readme.txt for WordPress store

** 1.1.7 **

* Changed the mapping of statuses between the EMS API and the store. Processing on the API side is equivalent to Pending on the store side.

** 1.2.0 **

* Filtering gateways depending on the store currency, supported currencies by the EMS Online service and supported currencies by payment methods.
  Sending to the API the currency selected by the consumer when creating an order.

** 1.2.1 **

* Fixed a bug related to processing a response for a refund Order

** 1.2.2 **

* Fixed shipping functionality for AfterPay and Klarna Pay Later

** 1.2.3 **

* Replaced 'reason' to 'customer_message' in error output
* Added request to retrieve a list of available currencies for every payment method.

** 1.2.4 **

* Fixed the functionality of changing the status for orders with a repeated payment attempt
* Fixed cases related to empty or invalid API key

** 1.2.5 **

* Added Woocommerce default payment methods.
* Enabled AfterPay.

** 1.3.0 **

* Refactored code to handle GPE solution.
* Unified bank labels to handle GPE solution.
* Added Bank Config class.
* Added Bank Gateway for handling custom bank functionality requests.
* Implemented GitHubActions.  
* Added AfterMerge PHPUnit test to check GPE solution GitHub actions.
* Added Sofort, KlarnaDirectDebit, GooglePay payment methods

** 1.3.1 **

* Fixed bug: After order's status changing creates BankTransfer order.
* Fixed bug: Refund doesn't work for payment methods that can be captured (AfterPay, KlarnaPayLater, KlarnaDirectDebit)

** 1.3.2 **

* Fixed bug: All the automated e-mails are no longer being send. 

** 1.3.3 **

* Fixed bug: an error is displayed on the store interface

** 1.3.4 **

* Added Apple Pay and Google Pay detection
* Updated the extra field in an order, Refactored PHPUnit tests to correspond the updated extra field
* Added Order Lines in each order
* Fixed bug: plugin will be deactivated after updating through store when it was installed using archive.

** 1.3.5 **

* Added default list of currency with EUR

** 1.3.6 **

* Removed unavailable payment methods
* Added caching the array of currency

** 1.3.7 **

* Added possibility to skip the intermediate page with terms of condition in AfterPay

** 1.3.8 **

* Added GiroPay payment method
* Added Api Key check
* Fixed bug: Fatal error appears when Api-key field has been filled and it was empty

** 1.3.9 **

* The plugin has been tested with the latest versions of WordPress (5.7, 5.8, 5.9, 6.0)
* Updated the "Library" module's description

** 1.3.10 **

* Fixed bug: user gets error "Merchant order line id's must be unique" when cart contains the same products as different items
* Added new payment methods: Swish, MobilePay,

** 1.3.11 **

* Fixed bug: user gets error "array offset on value of type bool" when the plugin has been installed in first time

** 1.3.12 **

* Fixed bug: user gets error "Warning: Trying to access array offset on value of type bool" when the plugin has been installed in first time
* Fixed bug: user gets email with error "Warning: Trying to access array offset on value of type bool"

** 1.3.13 **

* Added Viacash
* Changed payment method icons

** 1.3.14 **

* Fixed bug with order status mapping

** 1.3.15 **

* Updated additional addresses at customer

** 1.3.16 **

* Updated plugin settings: added checkbox that provides possibility to automatically change merchant’s order status to Completed

** 1.3.17 **

* Removed select in ideal payment method

** 1.3.18 **

* Removed AfterPay payment method