<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\ListPrice;

class CartSubscriber implements EventSubscriberInterface
{
    private const BOTTLE_COUNT_CUSTOM_FIELD = 'jules_bottle_count';

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
        if ($this->productRepository === null) {
            return; // Do nothing in admin
        }

        $lineItem = $event->getLineItem();
        if ($lineItem->getType() !== 'product' || !$lineItem->getPrice()) {
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

        $event->cancel();

        for ($i = 0; $i < $bottleCount; $i++) {
            $bottleLineItem = $this->createManualLineItem($product, $lineItem->getQuantity());
            $this->addPriceExtension($bottleLineItem, $bottleCount);
            $event->getCart()->add($bottleLineItem);
        }
    }

    private function createManualLineItem(ProductEntity $product, int $quantity): LineItem
    {
        $lineItem = new LineItem($product->getId(), LineItem::PRODUCT_LINE_ITEM_TYPE, $product->getId(), $quantity);
        $lineItem->setLabel($product->getTranslation('name'));
        $lineItem->setCover($product->getCover());

        $priceDefinition = new QuantityPriceDefinition(
            $product->getPrice()->getGross(),
            $product->getTax(),
            $quantity
        );

        $lineItem->setPriceDefinition($priceDefinition);

        if ($product->getPurchasePrices()) {
            $lineItem->setPayloadValue('purchasePrices', $product->getPurchasePrices());
        }
        if ($product->getIsCloseout()) {
            $lineItem->setPayloadValue('isCloseout', $product->getIsCloseout());
        }
        if ($product->getDeliveryTime()) {
            $lineItem->setDeliveryInformation(
                new \Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation(
                    $quantity,
                    $product->getWeight() * $quantity,
                    $product->getDeliveryTime()
                )
            );
        }

        return $lineItem;
    }

    private function addPriceExtension(LineItem $lineItem, int $divisor): void
    {
        $lineItem->addExtension('julesPriceDivider', new \Shopware\Core\Framework\Struct\ArrayStruct(['divisor' => $divisor]));
    }
}
