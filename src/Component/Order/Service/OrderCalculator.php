<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Order\Entity\Order;
use App\Component\OrderItem\Entity\OrderItem;
use App\Component\OrderPromotion\Entity\OrderPromotion;
use App\Component\Promotion\Entity\Promotion;

final class OrderCalculator
{
    public function recalculate(Order $order): void
    {
        $itemPromotions = $this->getSortedItemPromotions($order);

        $itemsTotalAfterItemDiscounts = 0;
        $hasAnyTaxableItem = false;
        $taxTotal = 0;

        foreach ($order->getItems() as $item) {
            $unitPrice = $item->getUnitPrice();
            $discountedUnitPrice = $unitPrice;

            foreach ($itemPromotions as $orderPromotion) {
                $promotion = $orderPromotion->getPromotion();
                if (!$this->isPromotionApplicableToItem($promotion, $item)) {
                    continue;
                }

                $discountedUnitPrice = $this->applyPercentageDiscount($discountedUnitPrice, $promotion->getPercentageDiscount());
            }

            if ($discountedUnitPrice !== $unitPrice) {
                $effectivePercent = $this->calculateEffectiveDiscountPercentage($unitPrice, $discountedUnitPrice);
                $item->setDiscount($effectivePercent);
            } else {
                $item->setDiscount(null);
            }

            $item->setDiscountValue($unitPrice - $discountedUnitPrice);
            $item->setDiscountedUnitPrice($discountedUnitPrice);
            $item->setDistributedOrderDiscountValue(0);

            $itemsTotalAfterItemDiscounts += $discountedUnitPrice * $item->getQuantity();
        }

        $orderDiscountTotal = 0;
        $orderPromotion = $order->getOrderPromotion();
        if ($orderPromotion !== null) {
            $orderDiscountTotal = $this->applyPercentageDiscount($itemsTotalAfterItemDiscounts, $orderPromotion->getPercentageDiscount());
            $orderDiscountTotal = $itemsTotalAfterItemDiscounts - $orderDiscountTotal;
        }

        $orderDiscountTotal = $this->distributeOrderDiscountAcrossItems($order, $itemsTotalAfterItemDiscounts, $orderDiscountTotal);

        $itemsTotalFinal = 0;
        foreach ($order->getItems() as $item) {
            $finalUnitPrice = $item->getDiscountedUnitPrice() - $item->getDistributedOrderDiscountValue();
            if ($finalUnitPrice < 0) {
                $finalUnitPrice = 0;
            }

            $item->setTotal($finalUnitPrice * $item->getQuantity());
            $item->setDiscountedUnitPrice($finalUnitPrice); // Include order discount in discountedUnitPrice

            $taxValue = $this->calculateItemTaxValue($item);
            $item->setTaxValue($taxValue);

            if ($taxValue !== null) {
                $hasAnyTaxableItem = true;
                $taxTotal += $taxValue;
            }

            $itemsTotalFinal += $item->getTotal();
        }

        $order->setTaxTotal($hasAnyTaxableItem ? $taxTotal : null);
        $order->setAdjustmentsTotal(-$orderDiscountTotal);
        $order->setTotal($itemsTotalAfterItemDiscounts - $orderDiscountTotal);

        if ($order->getTotal() < 0) {
            $order->setTotal(0);
        }

        // Order::itemsTotal is defined in contract as sum of all items values including item discounts, but BEFORE order-level discounts
        $order->setItemsTotal($itemsTotalAfterItemDiscounts);
    }

    /**
     * @return OrderPromotion[]
     */
    private function getSortedItemPromotions(Order $order): array
    {
        $promotions = $order->getItemPromotions()->toArray();

        usort(
            $promotions,
            static fn (OrderPromotion $a, OrderPromotion $b): int => $a->getPosition() <=> $b->getPosition()
        );

        return $promotions;
    }

    private function isPromotionApplicableToItem(Promotion $promotion, OrderItem $item): bool
    {
        if ($promotion->getType() !== Promotion::TYPE_ITEM) {
            return false;
        }

        $filters = $promotion->getProductTypesFilter();
        if ($filters === null || $filters === []) {
            return true;
        }

        $product = $item->getProduct();
        if ($product === null) {
            return false;
        }

        return in_array($product->getType(), $filters, true);
    }

    private function applyPercentageDiscount(int $amount, int $percentage): int
    {
        if ($percentage <= 0) {
            return $amount;
        }

        if ($percentage >= 100) {
            return 0;
        }

        $discount = (int) floor(($amount * $percentage) / 100);

        return $amount - $discount;
    }

    private function calculateEffectiveDiscountPercentage(int $originalUnitPrice, int $discountedUnitPrice): int
    {
        if ($originalUnitPrice <= 0) {
            return 0;
        }

        $discountValue = $originalUnitPrice - $discountedUnitPrice;

        return (int) floor(($discountValue * 100) / $originalUnitPrice);
    }

    private function distributeOrderDiscountAcrossItems(Order $order, int $itemsTotalAfterItemDiscounts, int $orderDiscountTotal): int
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
        }

        $distributedTotal = 0;
        foreach ($items as $idx => $item) {
            $item->setDistributedOrderDiscountValue($perUnitBase[$idx]);
            $distributedTotal += $perUnitBase[$idx] * $item->getQuantity();
        }

        return $distributedTotal;
    }

    private function calculateItemTaxValue(OrderItem $item): ?int
    {
        $product = $item->getProduct();
        if ($product === null) {
            return null;
        }

        $taxRate = $product->getTaxRate();
        if ($taxRate === null) {
            return null;
        }

        // Tax should be calculated on price after item discounts but BEFORE order-level discounts
        $priceBeforeOrderDiscount = $item->getDiscountedUnitPrice() * $item->getQuantity();

        return (int) floor(($priceBeforeOrderDiscount * $taxRate) / (100 + $taxRate));
    }

}
