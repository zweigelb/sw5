<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\AfterCartProcessEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private const BOTTLE_COUNT_CUSTOM_FIELD = 'jules_bottle_count';

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
            AfterCartProcessEvent::class => 'onAfterCartProcess',
        ];
    }

    public function onAfterCartProcess(AfterCartProcessEvent $event): void
    {
        // If the repository is not available (e.g., in admin), do nothing.
        if ($this->productRepository === null) {
            return;
        }

        $cart = $event->getCart();
        $context = $event->getSalesChannelContext();
        $lineItems = $cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        if ($lineItems->count() === 0) {
            return;
        }

        $productIds = $lineItems->getReferenceIds();

        $criteria = new Criteria($productIds);
        $criteria->addAssociation('customFields');

        $products = $this->productRepository->search($criteria, $context)->getEntities();

        $bottleCount = 0;
        foreach ($lineItems as $lineItem) {
            $itemBottleCount = 1; // Default to 1 bottle per item.

            /** @var ProductEntity|null $product */
            $product = $products->get($lineItem->getReferenceId());

            if ($product !== null) {
                $customFields = $product->getCustomFields();
                if (is_array($customFields) && isset($customFields[self::BOTTLE_COUNT_CUSTOM_FIELD])) {
                    $count = (int)$customFields[self::BOTTLE_COUNT_CUSTOM_FIELD];
                    if ($count > 0) {
                        $itemBottleCount = $count;
                    }
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
