<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\AfterCartProcessEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private const BOTTLE_COUNT_CUSTOM_FIELD = 'jules_bottle_count';

    public static function getSubscribedEvents(): array
    {
        return [
            AfterCartProcessEvent::class => 'onAfterCartProcess',
        ];
    }

    public function onAfterCartProcess(AfterCartProcessEvent $event): void
    {
        $cart = $event->getCart();
        $lineItems = $cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        if ($lineItems->count() === 0) {
            return;
        }

        $bottleCount = 0;
        foreach ($lineItems as $lineItem) {
            $itemBottleCount = 1; // Default to 1 bottle per item.

            // Custom fields that are enabled for the sales channel are available in the line item's payload.
            $customFields = $lineItem->getPayloadValue('customFields');

            if (is_array($customFields) && isset($customFields[self::BOTTLE_COUNT_CUSTOM_FIELD])) {
                $count = (int)$customFields[self::BOTTLE_COUNT_CUSTOM_FIELD];
                if ($count > 0) {
                    $itemBottleCount = $count;
                }
            }

            $bottleCount += ($itemBottleCount * $lineItem->getQuantity());
        }

        if ($bottleCount >= 12) {
            foreach ($cart->getShippingCosts() as $shippingCost) {
                $shippingCost->setUnitPrice(0);
                $shippingCost->setTotalPrice(0);
            }
        }
    }
}
