=== ConvesioPay ===
Tags: online payments, credit card
Requires at least: 5.0
Tested up to: 6.8.2
Stable tag: 1.9.7-rc-1
Requires PHP: 7.4
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Accept all worldwide payments in the US with ConvesioPay.


== Description ==

Expand into any market and automatically serve customers their preferred payment methods with one quick and easy integration via WooCommerce. Let your customers pay the way they want, no matter in which country they are. Offer access to all important local payment methods, including all major cards, mobile wallets like Apple Pay and WeChat Pay, and many more



== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/integration-convesiopay-woocommerce` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Go to WooCommerce->Settings->ConvesioPay to configure the plugin



== Screenshots ==

1. General settings



== Frequently Asked Questions ==

= Which payment methods does the ConvesioPay plugin support? =

At the moment we support the payment methods below:

* Credit Card

= Which countries are supported by ConvesioPay? =

ConvesioPay is a payment provider which currently supports payments in the US.

= Can visitors save their creditcard credentials? =

Yes, they can save their creditcard credentials. The creditcards will be securely saved within their WooCommerce account on your website.

This functionality is similar to the iFrame. For compliance reasons, we use tokenization to accomplish this and do not directly store payment details within the database of your website. When a payment is made, it makes use the payment token. ConvesioPay will know the payment details based on that token.



== Upgrade Notice ==

Every update comes with fixes and improvements.



== Changelog ==

### 1.9.7-rc-1 - 2026-09-04

* Fixed a PHP fatal on Checkout and My Account when a stored card has no supportedShopperInteractions
* Fixed a bug where subscription renewals stayed in Pending payment when the recurring token was missing or invalid

### 1.9.6 - 2025-10-01

* Fixed a bug where refund requests appear to fail even when successful

### 1.9.5 - 2025-09-30

* Fixed a bug with the not finding Authorize.net subscriptions sometimes in the migration manager
* Updated subscription migration manager pagination to better handle large datasets
* Added better error handling for failed subscription migrations
* Updated subscription migration manager to only look for active subscriptions

### 1.9.4 - 2025-09-10

* Webhook authentication and Connect form improvements

### 1.9.3 - 2025-09-04

* Added missing class

### 1.9.2 - 2025-09-04

* Misc bug fixes

### 1.9.1 - 2025-09-04

* Fixed a bug where the ApplePay session does not handle cart total updates (Shipping Options, Post Cart Upsell Plugins, etc)
* Fixed a bug with ApplePay not auto-submitting upon successful confirmation for Checkout Blocks.
* Fixed a bug with the webhook authentication verification not working sometimes.

### 1.9.0 - 2025-08-25

* Added card matching feature to the migration manager for Stripe subscriptions
* Fixed bug that caused erroneous customer ID generation for migrated subscriptions

### 1.8.0 - 2025-07-25

* Added search feature to be able to filter subscriptions in the migration manager

### 1.7.1 - 2025-07-23

* Fixed bug with some Authorize.net WC subscriptions not being identified for migration

### 1.7.0 - 2025-07-18

* Added support for migrating existing Authorize.net WC subscriptions to ConvesioPay

### 1.6.1 - 2025-07-12

* Auto-submit ApplePay orders after successful token confirmation

### 1.6.0 - 2025-07-09

* Added support for migrating existing Stripe WC subscriptions to ConvesioPay

### 1.5.3 - 2025-05-08

* Fixed bug with payment methods endpoint response not properly filtering by store

### 1.5.2 - 2025-04-14

* Additional brand icon styling fixes

### 1.5.1 - 2025-04-14

* Fixed card brand icon styling issue on checkout component

### 1.5.0 - 2025-02-28

* Remove irrelevant endpoint proxies

### 1.4.1 - 2025-02-28

* Fixed issue where WooCommerce translations are loading too early

### 1.4.0 - 2025-02-06

* Add support for WooCommerce Subscriptions

### 1.3.1 - 2025-01-03

* Fixed a typo with the ApplePay download certificate name

### 1.3.0 - 2025-01-03

* Add support for ApplePay

### 1.2.0 - 2024-12-03

* Add support for WooCommerce Checkout Blocks
* Fixed bug with `Payment was not successful` error message showing too early
* Fixed bug with recurring payments not working properly
* Fixed bug with dissapearing menu when clicking on Credit Card field `Name`
* Resolved PHP 8.2 warnings about dynamic properties

### 1.1.1 - 2024-12-02

* Fixed translation of payment methods not working

### 1.1.0 - 2024-11-22

* Settings page redesign
* New Logs section
* Fixed bug with enable/disable installments option

### 1.0.3 - 2024-10-30

* Don't hide the processing message when performing a redirecting payment
* Too small 3DS authentication pop-up window

### 1.0.2 - 2024-08-27

* Authorize store with admin client key
* Send card brand in payment requests

### 1.0.0 - 2024-07-19

* Initial release