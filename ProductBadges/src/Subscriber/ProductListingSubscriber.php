<?php declare(strict_types=1);

namespace Swag\ProductBadges\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductListingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingResultEvent::class => 'onProductListingResult'
        ];
    }

    public function onProductListingResult(ProductListingResultEvent $event): void
    {
        $products = $event->getResult()->getEntities();

        foreach ($products as $product) {
            if ($product->getShippingFree()) {
                $product->addExtension('free_shipping_badge', new ArrayStruct());
            }
        }
    }
}
