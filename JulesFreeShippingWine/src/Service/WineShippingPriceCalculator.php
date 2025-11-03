<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Shipping\ShippingException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

//TODO: Replace with actual Shopware interfaces and classes
interface ShippingPriceCalculatorInterface
{
    public function calculate(Cart $cart, SalesChannelContext $context): ShippingMethodPriceCollection;
}

class ShippingMethodPriceCollection
{
    public function add($price)
    {
    }
}

class WineShippingPriceCalculator implements ShippingPriceCalculatorInterface
{
    public function calculate(Cart $cart, SalesChannelContext $context): ShippingMethodPriceCollection
    {
        $bottleCount = 0;
        foreach ($cart->getLineItems() as $lineItem) {
            $product = $lineItem->getProduct();
            if ($product === null) {
                continue;
            }

            if ($this->isSixPack($product)) {
                $bottleCount += 6 * $lineItem->getQuantity();
            } elseif ($this->isSingleBottle($product)) {
                $bottleCount += $lineItem->getQuantity();
            }
        }

        $price = new ShippingMethodPriceCollection();
        if ($bottleCount >= 12) {
            $price->add(0.0);
        }

        return $price;
    }

    private function isSixPack($product): bool
    {
        // Add your logic to identify 6-pack products here (e.g., by tag, custom field, etc.)
        // For example, by tag:
        return $product->getTagIds() && \in_array('6-pack', $product->getTags()->getNames(), true);
    }

    private function isSingleBottle($product): bool
    {
        // Add your logic to identify single bottle products here
        return \in_array('wine', $product->getTags()->getNames(), true);
    }
}
