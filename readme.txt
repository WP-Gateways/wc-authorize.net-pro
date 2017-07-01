=== Authorize.Net Payment Gateway For WooCommerce ===
Contributors: mohsinoffline
Donate link: https://themebound.com/contact-us/send-payment/
Tags: woocommerce Authorize.Net, Authorize.Net, payment gateway, woocommerce, woocommerce payment gateway
Plugin URI: https://pledgedplugins.com/products/authorize-net-payment-gateway-woocommerce/
Author URI: https://pledgedplugins.com
Requires at least: 4.0, WooCommerce 3.0.0
Tested up to: 4.9
Stable tag: 4.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin enables you to use the Authorize.Net payment gateway and accept credit cards directly on your WooCommerce powered WordPress e-commerce website without redirecting customers away to the gateway website.

== Description ==

[Authorize.Net](https://www.authorize.net/) Payment Gateway allows you to accept credit cards from all over the world on your websites and deposit funds automatically into your merchant bank account.

[WooCommerce](https://woocommerce.com/) is one of the oldest and most powerful e-commerce solutions for WordPress. This platform is very widely supported in the WordPress community which makes it easy for even an entry level e-commerce entrepreneur to learn to use and modify.

Here's a list of what the plugin provides out of the box:

* **Easy Install**:Like all Pledged Plugins add-ons, this plugin installs with one click. After installing, you will have only a few fields to fill out before you are ready to accept credit cards on your store.
* **Secure Credit Card Processing**: Securely process credit cards without redirecting your customers to the gateway website.
* **Authorize Now, Capture Later**: Optionally choose only to authorize transactions, and capture at a later date.
* **Restrict Card Types**: Optionally choose to restrict certain card types and the plugin will hide its icon and provide a proper error message on checkout.
* **Gateway Receipts**: Optionally choose to send receipts from your Authorize.Net merchant account.
* **Logging**: Enable logging so you can debug issues that arise if any.

**Additional Information**
-------
A valid SSL certificate is required to ensure your customer credit card details are safe and make your site PCI DSS compliant. This plugin does not store the customer credit card numbers or sensitive information on your website.

**Extend, Contribute, Integrate**
-------

Visit the [plugin page](https://pledgedplugins.com/products/authorize-net-payment-gateway-woocommerce/) for more details. Contributors are welcome to send pull requests via [Bitbucket repository](https://bitbucket.org/pledged/authorize.net/).

For custom integration with your WordPress website, please [contact us here](https://wpgateways.com/support/custom-payment-gateway-integration/).

Disclaimer: This plugin is not affiliated with or supported by Authorize.Net, WooCommerce.com or Automattic. All logos and trademarks are the property of their respective owners. 

== Installation ==

1. Upload `woo-authorize-net-gateway-aim` folder/directory to the `/wp-content/plugins/` directory
2. Activate the plugin (Wordpress -> Plugins).
3. Go to the WooCommerce settings page (Wordpress -> WooCommerce -> Settings) and select the Checkout tab.
4. Under the Checkout tab, you will find all the available payment gateways. Find the 'Authorize.Net' subtab and click it.
5. On this page you will find all of the configuration options for this payment gateway.
6. Enable the method by using the checkbox.
7. Enter the Authorize.Net account details (API Login ID, Transaction Key).

That's it! You are ready to accept credit cards with your Authorize.Net payment gateway now connected to WooCommerce.


== Changelog ==

= 4.0.0 =

* Added "authorize only" option
* Added logging option
* Added option to restrict card types
* Added test mode option and made HTTPS mandatory for live mode
* Passed billing details to "Pay for Order" page
* Complete overhaul of the plugin with massive improvements to the code base

= 3.5.2 =

* Updated transaction endpoint URL

= 3.5.1 =

* Made "Order Received" link dynamic
* Included customer IP in the data sent to the gateway
* Added POT file for translation

= 3.5 =

* Fixed compatibility issues with other payment gateway plugins

= 3.2.1 =

* Compatible to WooCommerce 2.3.x
* Compatible to WordPress 4.x

= 3.0 =

* Compatible to WooCommerce 2.2.2
* Compatible to WordPress 4.0

= 2.0 =

* Compatible to WooCommerce 2.1.1