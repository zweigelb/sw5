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
    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    public function __construct(SalesChannelRepositoryInterface $productRepository)
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
        $cart = $event->getCart();
        $context = $event->getSalesChannelContext();
        $lineItems = $cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        if ($lineItems->count() === 0) {
            return;
        }

        $productIds = $lineItems->getReferenceIds();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $productIds));
        $criteria->addAssociation('tags');

        $products = $this->productRepository->search($criteria, $context)->getEntities();

        if ($products->count() === 0) {
            return; // No products found, so can't determine tags.
        }

        $bottleCount = 0;
        foreach ($lineItems as $lineItem) {
            /** @var ProductEntity|null $product */
            $product = $products->get($lineItem->getReferenceId());

            if ($product !== null && $this->isSixPack($product)) {
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
