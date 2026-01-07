<?php declare(strict_types=1);

namespace Swag\ProductBadges\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductListingCriteriaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'onProductListingCriteria'
        ];
    }

    public function onProductListingCriteria(ProductListingCriteriaEvent $event): void
    {
        $event->getCriteria()->addAssociation('shippingFree');
    }
}
