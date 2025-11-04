<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LineItemSubscriber implements EventSubscriberInterface
{
    private const BOTTLE_COUNT_CUSTOM_FIELD = 'jules_bottle_count';
    public const BOTTLE_COUNT_PAYLOAD_KEY = 'julesBottleCount';

    /**
     * @var SalesChannelRepositoryInterface|null
     */
    private $productRepository;

    public function __construct(?SalesChannelRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAdded',
        ];
    }

    public function onBeforeLineItemAdded(BeforeLineItemAddedEvent $event): void
    {
        // In case this is loaded in the admin panel, do nothing.
        if ($this->productRepository === null) {
            return;
        }

        $lineItem = $event->getLineItem();
        $context = $event->getSalesChannelContext();

        if ($lineItem->getType() !== 'product') {
            return;
        }

        $productId = $lineItem->getReferencedId();
        if (!$productId) {
            return;
        }

        // We must fetch the product to access its custom fields.
        $criteria = new Criteria([$productId]);
        /** @var ProductEntity|null $product */
        $product = $this->productRepository->search($criteria, $context)->get($productId);

        if ($product === null) {
            return;
        }

        $customFields = $product->getCustomFields();
        if (isset($customFields[self::BOTTLE_COUNT_CUSTOM_FIELD])) {
            $count = (int)$customFields[self::BOTTLE_COUNT_CUSTOM_FIELD];
            if ($count > 0) {
                // "Stamp" the bottle count onto the line item's payload.
                $lineItem->setPayloadValue(self::BOTTLE_COUNT_PAYLOAD_KEY, $count);
            }
        }
    }
}
