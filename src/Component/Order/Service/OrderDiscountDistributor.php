<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Order\Entity\Order;
use App\Component\OrderItem\Entity\OrderItem;

final class OrderDiscountDistributor
{
    /**
     * Distribute order-level discount across all items proportionally.
     *
     * Uses a proportional distribution algorithm that:
     * 1. Calculates base per-unit discount for each item
     * 2. Handles remainders by distributing to items with highest fractional parts
     *
     * @param Order $order The order containing items
     * @param int $itemsTotalAfterItemDiscounts Total value of items after item discounts
     * @param int $orderDiscountTotal Total order-level discount to distribute
     * @return int Actual total distributed discount
     */
    public function distribute(Order $order, int $itemsTotalAfterItemDiscounts, int $orderDiscountTotal): int
    {
        if ($orderDiscountTotal <= 0 || $itemsTotalAfterItemDiscounts <= 0) {
            foreach ($order->getItems() as $item) {
                $item->setDistributedOrderDiscountValue(0);
            }
            return 0;
        }

        $items = $order->getItems()->toArray();

        $allocated = 0;
        $perUnitBase = [];
        $perUnitFraction = [];

        foreach ($items as $idx => $item) {
            $discountedUnit = $item->getDiscountedUnitPrice();
            $numerator = $orderDiscountTotal * $discountedUnit;
            $perUnit = (int) floor($numerator / $itemsTotalAfterItemDiscounts);
            $fraction = $numerator % $itemsTotalAfterItemDiscounts;

            $perUnitBase[$idx] = $perUnit;
            $perUnitFraction[$idx] = $fraction;
            $allocated += $perUnit * $item->getQuantity();
        }

        $remaining = $orderDiscountTotal - $allocated;
        if ($remaining > 0) {
            $remaining = $this->distributeRemainder($items, $perUnitBase, $perUnitFraction, $remaining);
        }

        return $this->applyDistributedDiscounts($items, $perUnitBase);
    }

    /**
     * Distribute remaining discount to items with highest fractional parts.
     *
     * @param OrderItem[] $items
     * @param int[] &$perUnitBase Base discount per unit (modified by reference)
     * @param int[] $perUnitFraction Fractional parts for tie-breaking
     * @param int $remaining Remaining discount to distribute
     * @return int Remaining discount after distribution
     */
    private function distributeRemainder(array $items, array &$perUnitBase, array $perUnitFraction, int $remaining): int
    {
        arsort($perUnitFraction);
        
        foreach ($perUnitFraction as $idx => $fraction) {
            if ($remaining <= 0) {
                break;
            }

            /** @var OrderItem $item */
            $item = $items[$idx];

            $qty = $item->getQuantity();
            if ($qty <= 0) {
                continue;
            }

            if ($remaining < $qty) {
                continue;
            }

            $perUnitBase[$idx] += 1;
            $remaining -= $qty;
        }

        return $remaining;
    }

    /**
     * Apply calculated distributed discounts to all items.
     *
     * @param OrderItem[] $items
     * @param int[] $perUnitBase Discount per unit for each item
     * @return int Total distributed discount
     */
    private function applyDistributedDiscounts(array $items, array $perUnitBase): int
    {
        $distributedTotal = 0;
        
        foreach ($items as $idx => $item) {
            $item->setDistributedOrderDiscountValue($perUnitBase[$idx]);
            $distributedTotal += $perUnitBase[$idx] * $item->getQuantity();
        }

        return $distributedTotal;
    }
}
