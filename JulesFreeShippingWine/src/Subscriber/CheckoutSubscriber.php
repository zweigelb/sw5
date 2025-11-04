<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\AfterCartProcessEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
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
            // Read the "stamped" bottle count from the payload.
            $itemBottleCount = $lineItem->getPayloadValue(LineItemSubscriber::BOTTLE_COUNT_PAYLOAD_KEY);

            // If the stamp exists and is a valid number, use it. Otherwise, default to 1.
            if ($itemBottleCount && is_int($itemBottleCount) && $itemBottleCount > 0) {
                $bottleCount += ($itemBottleCount * $lineItem->getQuantity());
            } else {
                $bottleCount += $lineItem->getQuantity();
            }
        }

        if ($bottleCount >= 12) {
            $shippingCosts = $cart->getShippingCosts();
            $shippingCosts->setUnitPrice(0);
            $shippingCosts->setTotalPrice(0);
        }
    }
}
