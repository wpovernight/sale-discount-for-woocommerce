=== Plugin Name ===
Contributors: wpovernight
Donate link: https://wpovernight.com/
Tags: woocommerce, regular price, discount, sale price
Requires at least: 4.9
Tested up to: 5.9
Requires PHP: 7.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stores the regular price of products in the order data so that the customer sees the discount they received in email/account/invoice when an item was on sale

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

= 1.0.0 =
* First public release 🎉