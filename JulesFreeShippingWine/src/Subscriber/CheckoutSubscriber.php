<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\AfterCartProcessEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    // The custom field's technical name.
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

            $customFields = $lineItem->getPayloadValue('customFields');

            if (isset($customFields[self::BOTTLE_COUNT_CUSTOM_FIELD])) {
                $count = (int)$customFields[self::BOTTLE_COUNT_CUSTOM_FIELD];
                // Ensure the custom field value is a positive number.
                if ($count > 0) {
                    $itemBottleCount = $count;
                }
            }

            $bottleCount += ($itemBottleCount * $lineItem->getQuantity());
        }

        if ($bottleCount >= 12) {
            $shippingCosts = $cart->getShippingCosts();
            $shippingCosts->setUnitPrice(0);
            $shippingCosts->setTotalPrice(0);
        }
    }
}
