=== Sale price as order discount for WooCommerce ===
Contributors: wpovernight
Donate link: https://wpovernight.com/
Tags: woocommerce, regular price, discount, sale price
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.11
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Saves product regular price in order data to show customers the discount received in email, account, and invoice when items are on sale.

== Description ==

When you set a sale price for a product in WooCommerce, or when the price of a product is modified by 3rd party plugins, WooCommerce does not store this price (or discount) in the order data by default. As a result, once the order is placed, the customer doesn't see the discount they got in the email or invoice they receive, and the admin doesn't see this in the order details either.
This is because WooCommerce only considers *coupon discounts* as discounts, whereas a change in price (a sale price or a programmatically modified price) is simply regarded as the actual price of the product.

This plugin addresses that issue by copying the 'regular price' from the product data as soon as the order is created (either in the backend or via the checkout). This price is then set as the item's "pre-discount" price (leaving the actual price paid untouched). The result is that WooCommerce will show this the same way it shows coupon discounts.


== Frequently Asked Questions ==

= Something is not working correctly =

Please post a message to our [support forum](https://wordpress.org/support/plugin/sale-discount-for-woocommerce/) and we'll do our best to help resolve your issue!

= How can I contribute to this project? =

This project is hosted on github: https://github.com/wpovernight/sale-discount-for-woocommerce
If you want to contribute to the code, feel free to submit a PR. You can also open issues on Github, although we encourage you to open a ticket in the support forum here on WordPress.org first if you're not absolutely sure something is a bug.

== Changelog ==

= 1.1.11 (2025-05-13) =
* New: Minimum PHP version requirement updated to 7.4
* Tested up to WooCommerce 9.8 & WordPress 6.8

= 1.1.10 (2024-11-04) =
* Fix: remove files from SVN that were mistakenly left undeleted

= 1.1.9 (2024-10-30) =
* New: comply with WP Plugin Check standards

= 1.1.8 (2024-10-23) =
* Fix: issue with HPOS compatibility declaration

= 1.1.7 (2024-10-14) =
* Fix: Load plugin translations later in the `init` hook.
* Tested: Compatible with WooCommerce 9.4.

= 1.1.6 (2024-06-26) =
* Tested up to WooCommerce 9.0 & WordPress 6.6

= 1.1.5 (2024-03-20) =
* Translations: updated translation template (POT)
* Tested up to WooCommerce 8.7 & WordPress 6.5

= 1.1.4 (2023-11-08) =
* Tested up to WooCommerce 8.3 & WordPress 6.4

= 1.1.3 (2023-08-09) =
* Tested up to WooCommerce 8.0 & WordPress 6.3

= 1.1.2 =
* Tested up to WooCommerce 7.6 & WordPress 6.2

= 1.1.1 =
* Declared compatibility with WooCommerce HPOS
* Tested up to WooCommerce 7.1 & WordPress 6.1

= 1.1.0 =
* New: Allow storing discount for non-sale items (filter: `wpo_wc_sale_discount_apply_to_item`)

= 1.0.1 =
* Fix: Loading textdomain
* Translations: Added Spanish

= 1.0.0 =
* First public release