<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\AfterCartProcessEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {
        // Constructor is now empty as we no longer need the repository.
    }

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
            // Check for the 'isSixPack' string flag in the item's payload.
            if ($lineItem->getPayloadValue('isSixPack') === 'true') {
                $bottleCount += 6 * $lineItem->getQuantity();
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
