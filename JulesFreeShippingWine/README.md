# Jules Free Shipping Wine

This plugin provides free shipping for orders where the total "bottle count" is 12 or more. The bottle count is determined by a custom field on your products.

## Installation

1.  Copy the `JulesFreeShippingWine` directory to the `custom/plugins` directory of your Shopware installation.
2.  Install and activate the plugin in the Shopware administration.

## Product Configuration (IMPORTANT)

This plugin uses **Custom Fields** to determine the number of "bottles" in each product. You must create this custom field before the plugin will work.

### Step 1: Create the Custom Field Set

1.  In your Shopware Admin, go to **Settings > System > Custom Fields**.
2.  Click **Add new set**.
3.  Name the set `Product Bottle Count` (or similar).
4.  For the **Assign to** option, select **Products**.
5.  Click **Save**.

### Step 2: Create the Custom Field

1.  After saving the set, a **New custom field** section will appear. Click **Add custom field**.
2.  For the **Type**, select **Number**.
3.  For the **Technical name**, you **MUST** enter exactly: `jules_bottle_count`
4.  For the **Label**, you can enter something descriptive, like `Number of Bottles`.
5.  Click **Save**.

### Step 3: Set the Bottle Count on Your Products

1.  Go to any product that should count as more than one bottle (e.g., your "6-pack" products).
2.  Scroll down to the **Specifications** section.
3.  You should see your new "Product Bottle Count" custom field set.
4.  In the "Number of Bottles" field, enter the number of bottles for that product (e.g., `6`).
5.  Click **Save**.

**That's it!** Any product with a number in this field will be counted accordingly. All other products in your store will automatically be counted as **1 bottle** each.
