# Changelog WooCommerce

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

* Filtering gateways depending on the store currency, supported gateway currencies and supported currencies by the EMC Online service.
  Sending to the API the currency selected by the consumer when creating an order.