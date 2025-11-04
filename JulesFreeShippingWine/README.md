# Jules Free Shipping Wine

This plugin provides free shipping for orders of 12 or more bottles.

## Installation

1.  Copy the `JulesFreeShippingWine` directory to the `custom/plugins` directory of your Shopware installation.
2.  Install and activate the plugin in the Shopware administration.

## Configuration

This plugin works automatically with your existing shipping methods. Simply ensure you have a standard shipping method configured. If a customer's cart contains 12 or more bottles, the shipping cost for that order will automatically be set to zero.

## Product Configuration

For this plugin to work correctly, you must tag your 6-pack products:

*   **6-packs:** Products that are a 6-pack should be tagged with `6-pack`.
*   **Other Items:** All other items in your store are automatically counted as a single bottle. No special configuration is needed for them.
