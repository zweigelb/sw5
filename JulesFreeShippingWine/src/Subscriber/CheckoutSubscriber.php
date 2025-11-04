<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutCartEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    public function __construct(EntityRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartEvent::class => 'onCheckoutCart',
        ];
    }

    public function onCheckoutCart(CheckoutCartEvent $event): void
    {
        $cart = $event->getCart();
        $context = $event->getSalesChannelContext();

        $productLineItems = $cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        if ($productLineItems->count() === 0) {
            return;
        }

        $productIds = $productLineItems->getReferenceIds();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $productIds));
        $criteria->addAssociation('tags');
        $criteria->addAssociation('categories');

        $products = $this->productRepository->search($criteria, $context->getContext())->getEntities();

        if ($products->count() === 0) {
            return;
        }

        $bottleCount = 0;
        foreach ($productLineItems as $lineItem) {
            /** @var ProductEntity|null $product */
            $product = $products->get($lineItem->getReferenceId());

            if ($product === null) {
                continue;
            }

            if ($this->isSixPack($product)) {
                $bottleCount += 6 * $lineItem->getQuantity();
            } elseif ($this->isSingleBottle($product)) {
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
            if ($tag->getName() === '6-pack') {
                return true;
            }
        }

        return false;
    }

    private function isSingleBottle(ProductEntity $product): bool
    {
        $categories = $product->getCategories();
        if ($categories === null) {
            return false;
        }

        foreach ($categories as $category) {
            if ($category->getName() === 'Wine') {
                return true;
            }
        }

        return false;
    }
}
