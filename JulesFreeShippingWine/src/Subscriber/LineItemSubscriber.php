<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LineItemSubscriber implements EventSubscriberInterface
{
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
        // If the repository is null, we are in the admin panel, so do nothing.
        if ($this->productRepository === null) {
            return;
        }

        $lineItem = $event->getLineItem();
        $context = $event->getSalesChannelContext();

        // Only act on product line items
        if ($lineItem->getType() !== 'product') {
            return;
        }

        $productId = $lineItem->getReferencedId();
        if (!$productId) {
            return;
        }

        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('tags');

        /** @var ProductEntity|null $product */
        $product = $this->productRepository->search($criteria, $context)->get($productId);

        if ($product === null) {
            return;
        }

        // Check for the 6-pack tag and add a flag to the line item's payload
        if ($this->isSixPack($product)) {
            $lineItem->setPayloadValue('isSixPack', true);
        }
    }

    private function isSixPack(ProductEntity $product): bool
    {
        $tags = $product->getTags();
        if ($tags === null) {
            return false;
        }

        foreach ($tags as $tag) {
            if (strtolower(trim($tag->getName())) === '6-pack') {
                return true;
            }
        }

        return false;
    }
}
