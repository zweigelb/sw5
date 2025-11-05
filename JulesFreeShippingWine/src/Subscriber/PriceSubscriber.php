<?php declare(strict_types=1);

namespace Jules\FreeShippingWine\Subscriber;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceDefinitionInterface;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\Price\PriceCalculatorInterface;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;

class PriceSubscriber implements PriceCalculatorInterface
{
    private $originalCalculator;

    public function __construct(PriceCalculatorInterface $originalCalculator)
    {
        $this->originalCalculator = $originalCalculator;
    }

    public function calculate(PriceDefinitionInterface $definition, SalesChannelContext $context): CalculatedPrice
    {
        $price = $this->originalCalculator->calculate($definition, $context);

        if ($definition instanceof QuantityPriceDefinition) {
            /** @var ArrayStruct|null $extension */
            $extension = $definition->getExtension('julesPriceDivider');
            if ($extension !== null && $extension->has('divisor') && $extension->get('divisor') > 1) {
                $divisor = $extension->get('divisor');
                $newUnitPrice = $price->getUnitPrice() / $divisor;
                $newTotalPrice = $price->getTotalPrice() / $divisor;

                // CORRECTED: Also divide the calculated taxes.
                $newTaxes = new CalculatedTaxCollection();
                /** @var CalculatedTax $tax */
                foreach ($price->getCalculatedTaxes() as $tax) {
                    $newTaxes->add(new CalculatedTax($tax->getTax() / $divisor, $tax->getTaxRate(), $newTotalPrice));
                }

                return new CalculatedPrice(
                    $newUnitPrice,
                    $newTotalPrice,
                    $newTaxes,
                    $price->getTaxRules(),
                    $price->getQuantity()
                );
            }
        }

        return $price;
    }
}
