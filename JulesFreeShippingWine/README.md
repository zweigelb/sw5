# Jules Free Shipping Wine (v1.0.9)

This plugin works with Shopware's own shipping rules. It ensures that products with a "bottle count" are added to the cart as multiple items, allowing your existing shipping rules (e.g., "free shipping for 12 or more items") to work correctly.

## Installation

1.  Copy the `JulesFreeShippingWine` directory to the `custom/plugins` directory of your Shopware installation.
2.  Install and activate the plugin in the Shopware administration.

## Product Configuration (IMPORTANT)

This plugin uses a **Custom Field** to determine how many items a single product should represent in the cart.

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

1.  Go to any product that should count as more than one item (e.g., your "6-pack" products).
2.  Scroll down to the section for your custom fields (e.g., "Product Information").
3.  In the "Number of Bottles" field, enter the number of items that product represents (e.g., `6`).
4.  Click **Save**.

**That's it!** Now, when you add a "6-pack" to the cart, it will appear as 6 individual items, and the price will be divided accordingly. This allows Shopware's own shipping rules to correctly calculate the total number of items in the cart.
