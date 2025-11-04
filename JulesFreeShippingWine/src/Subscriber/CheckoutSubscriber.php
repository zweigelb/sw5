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

        $products = $this->productRepository->search($criteria, $context->getContext())->getEntities();

        if ($products->count() === 0) {
            // This can happen if products are removed while the cart is active.
            // We can just count the remaining items as single bottles.
            $bottleCount = $productLineItems->getQuantity();
        } else {
            $bottleCount = 0;
            foreach ($productLineItems as $lineItem) {
                /** @var ProductEntity|null $product */
                $product = $products->get($lineItem->getReferenceId());

                if ($product !== null && $this->isSixPack($product)) {
                    $bottleCount += 6 * $lineItem->getQuantity();
                } else {
                    $bottleCount += $lineItem->getQuantity();
                }
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
}
