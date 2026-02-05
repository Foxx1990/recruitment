<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\OrderItem\Entity\OrderItem;

final class TaxCalculator
{
    /**
     * Calculate tax value for an order item based on its product's tax rate.
     *
     * @param OrderItem $item The order item to calculate tax for
     * @return int|null Tax value in cents, or null if product has no tax rate
     */
    public function calculateItemTax(OrderItem $item): ?int
    {
        $product = $item->getProduct();
        if ($product === null) {
            return null;
        }

        $taxRate = $product->getTaxRate();
        if ($taxRate === null) {
            return null;
        }

        $total = $item->getTotal();

        return (int) floor(($total * $taxRate) / (100 + $taxRate));
    }
}
