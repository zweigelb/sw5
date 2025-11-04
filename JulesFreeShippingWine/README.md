# Jules Free Shipping Wine (v1.0.6)

This plugin provides free shipping for orders where the total "bottle count" is 12 or more. The bottle count is determined by a custom field on your products.

## Installation

1.  Copy the `JulesFreeShippingWine` directory to the `custom/plugins` directory of your Shopware installation.
2.  Install and activate the plugin in the Shopware administration.

## Product Configuration (IMPORTANT)

This plugin uses a **Custom Field** to determine the number of "bottles" in each product. You must create this custom field for the plugin to work.

### Step 1: Create the Custom Field

1.  In your Shopware Admin, go to **Settings > System > Custom Fields**.
2.  Click **Add new set** (or add to an existing set for products).
3.  Name the set `Product Information` (or similar) and assign it to **Products**. Click **Save**.
4.  Within the set, click **Add custom field**.
5.  For the **Type**, select **Number**.
6.  For the **Technical name**, you **MUST** enter exactly: `jules_bottle_count`
7.  For the **Label**, you can enter something descriptive, like `Number of Bottles`.
8.  Click **Save**.

### Step 2: Set the Bottle Count on Your Products

1.  Go to any product that should count as more than one bottle (e.g., your "6-pack" products).
2.  Scroll down to the section for your custom fields (e.g., "Product Information").
3.  In the "Number of Bottles" field, enter the number of bottles for that product (e.g., `6`).
4.  Click **Save**.

**That's it!** Any product with a number in this field will be counted accordingly. All other products in your store will automatically be counted as **1 bottle** each.

## Troubleshooting

**Problem: The bottle count is not being updated in the cart.**

If you have configured your custom fields but the shipping costs are still not being calculated correctly, the most likely cause is caching.

**Solution: Clear the Shopware Cache**

1.  In your Shopware Admin, go to **Settings > System > Caches & Indexes**.
2.  Click the **Clear Caches** button.

After clearing the cache, you **must remove the items from your cart and add them again**. The bottle count is saved only when you first add an item to the cart.
