git# Changelog WooCommerce

** 1.0.0 **

- Initial version

** 1.0.8 **

- Fix Captured and shipped functionality

** 1.0.9 **

- Move files from trunk folder to root folder

** 1.0.10 **

- Fixed sending peyment information in the New Order email for all gateways

** 1.0.11 **

- Add Refund Order functionality

** 1.0.12 **

- Captured transaction status check changed to has-captures flag in accordance with the new behavior of the capture

** 1.0.13 **

- Changed redirect in KP Later (after Order create in the EMS) to 'payment_url'

** 1.0.14 **

- Fixed refund order functionality

** 1.0.15 **

- Adding refund admin description
- Removing empty values before sending data to the API

** 1.1.0 **

- Added the ability for AfterPay to be available in the selected countries.
- Removing Klarna Pay Later fields gender and birthday from checkout form and customer object
- Replaced locally stored ginger-php library on composer library installer.

** 1.1.1 **

- Removed the Webhook option
- Updated EMS Online plugin description in README

** 1.1.2 **

- Expanded updating of order statuses in the store

** 1.1.3 **

- Fixed shipping tax rate functionality

** 1.1.4 **

- Appended processing order post status for correct status update to 'complemented' by WooCommerce
  Updated README

** 1.1.5 **

- Changes regarding to WordPress requirements for placing a plugin on the WordPress store

** 1.1.6 **

- Updated readme.txt for WordPress store

** 1.1.7 **

- Changed the mapping of statuses between the EMS API and the store. Processing on the API side is equivalent to Pending on the store side.

** 1.2.0 **

- Filtering gateways depending on the store currency, supported currencies by the EMS Online service and supported currencies by payment methods.
  Sending to the API the currency selected by the consumer when creating an order.

** 1.2.1 **

- Fixed a bug related to processing a response for a refund Order

** 1.2.2 **

- Fixed shipping functionality for AfterPay and Klarna Pay Later

** 1.2.3 **

- Replaced 'reason' to 'customer_message' in error output
- Added request to retrieve a list of available currencies for every payment method.

** 1.2.4 **

- Fixed the functionality of changing the status for orders with a repeated payment attempt
- Fixed cases related to empty or invalid API key

** 1.2.5 **

- Added Woocommerce default payment methods.
- Enabled AfterPay.

** 1.3.0 **

- Refactored code to handle GPE solution.
- Unified bank labels to handle GPE solution.
- Added Bank Config class.
- Added Bank Gateway for handling custom bank functionality requests.
- Implemented GitHubActions.
- Added AfterMerge PHPUnit test to check GPE solution GitHub actions.
- Added Sofort, KlarnaDirectDebit, GooglePay payment methods

** 1.3.1 **

- Fixed bug: After order's status changing creates BankTransfer order.
- Fixed bug: Refund doesn't work for payment methods that can be captured (AfterPay, KlarnaPayLater, KlarnaDirectDebit)

** 1.3.2 **

- Fixed bug: All the automated e-mails are no longer being send.

** 1.3.3 **

- Fixed bug: an error is displayed on the store interface

** 1.3.4 **

- Added Apple Pay and Google Pay detection
- Updated the extra field in an order, Refactored PHPUnit tests to correspond the updated extra field
- Added Order Lines in each order
- Fixed bug: plugin will be deactivated after updating through store when it was installed using archive.

** 1.3.5 **

- Added default list of currency with EUR

** 1.3.6 **

- Removed unavailable payment methods
- Added caching the array of currency

** 1.3.7 **

- Added possibility to skip the intermediate page with terms of condition in AfterPay

** 1.3.8 **

- Added GiroPay payment method
- Added Api Key check
- Fixed bug: Fatal error appears when Api-key field has been filled and it was empty

** 1.3.9 **

- The plugin has been tested with the latest versions of WordPress (5.7, 5.8, 5.9, 6.0)
- Updated the "Library" module's description

** 1.3.10 **

- Fixed bug: user gets error "Merchant order line id's must be unique" when cart contains the same products as different items
- Added new payment methods: Swish, MobilePay,

** 1.3.11 **

- Fixed bug: user gets error "array offset on value of type bool" when the plugin has been installed in first time

** 1.3.12 **

- Fixed bug: user gets error "Warning: Trying to access array offset on value of type bool" when the plugin has been installed in first time
- Fixed bug: user gets email with error "Warning: Trying to access array offset on value of type bool"

** 1.3.13 **

- Added Viacash
- Changed payment method icons

** 1.3.14 **

- Fixed bug with order status mapping

** 1.3.15 **

- Updated additional addresses at customer

** 1.3.16 **

- Updated plugin settings: added checkbox that provides possibility to automatically change merchant’s order status to Completed

** 1.3.17 **

- Removed select in ideal payment method
