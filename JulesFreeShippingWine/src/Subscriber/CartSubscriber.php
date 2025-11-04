<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\Cart\ProductLineItemFactory;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private const BOTTLE_COUNT_CUSTOM_FIELD = 'jules_bottle_count';

    private $productRepository;
    private $productLineItemFactory;

    public function __construct(
        ?SalesChannelRepositoryInterface $productRepository,
        ProductLineItemFactory $productLineItemFactory
    ) {
        $this->productRepository = $productRepository;
        $this->productLineItemFactory = $productLineItemFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAdded',
        ];
    }

    public function onBeforeLineItemAdded(BeforeLineItemAddedEvent $event): void
    {
        if ($this->productRepository === null) {
            return; // Do nothing in admin
        }

        $lineItem = $event->getLineItem();
        if ($lineItem->getType() !== 'product') {
            return;
        }

        $productId = $lineItem->getReferencedId();
        if (!$productId) {
            return;
        }

        $context = $event->getSalesChannelContext();

        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('customFields');

        /** @var ProductEntity|null $product */
        $product = $this->productRepository->search($criteria, $context)->get($productId);

        if ($product === null) {
            return;
        }

        $customFields = $product->getCustomFields();
        if (!is_array($customFields) || !isset($customFields[self::BOTTLE_COUNT_CUSTOM_FIELD])) {
            return; // No custom field, so do nothing.
        }

        $bottleCount = (int)$customFields[self::BOTTLE_COUNT_CUSTOM_FIELD];
        if ($bottleCount <= 1) {
            return; // Custom field is set to 1 or less, so treat as a single item.
        }

        // CORRECTED: Cancel the original "add to cart" event completely.
        $event->cancel();

        // Create the new, single "bottle" line items.
        for ($i = 0; $i < $bottleCount; $i++) {
            $bottleLineItem = $this->productLineItemFactory->create($productId, ['quantity' => $lineItem->getQuantity()]);
            $this->addPriceExtension($bottleLineItem, $bottleCount);
            $event->getCart()->add($bottleLineItem);
        }
    }

    private function addPriceExtension(LineItem $lineItem, int $divisor): void
    {
        // Add an extension to the line item that our price calculator will use.
        $lineItem->addExtension('julesPriceDivider', new \Shopware\Core\Framework\Struct\ArrayStruct(['divisor' => $divisor]));
    }
}
