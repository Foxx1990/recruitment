<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

use App\Component\Order\Entity\Order;
use App\Component\OrderItem\Entity\OrderItem;
use App\Component\OrderPromotion\Entity\OrderPromotion;
use App\Component\Promotion\Entity\Promotion;

final class ItemDiscountApplicator
{
    public function __construct(
        private readonly PercentageDiscountCalculator $discountCalculator,
    ) {
    }

    /**
     * Apply item-level promotions to all items in the order.
     *
     * @param Order $order The order to apply item discounts to
     * @return int Total value of items after item-level discounts
     */
    public function applyItemDiscounts(Order $order): int
    {
        $itemPromotions = $this->getSortedItemPromotions($order);
        $itemsTotalAfterItemDiscounts = 0;

        foreach ($order->getItems() as $item) {
            $unitPrice = $item->getUnitPrice();
            $discountedUnitPrice = $unitPrice;

            foreach ($itemPromotions as $orderPromotion) {
                $promotion = $orderPromotion->getPromotion();
                if (!$this->isPromotionApplicableToItem($promotion, $item)) {
                    continue;
                }

                $discountedUnitPrice = $this->discountCalculator->calculate(
                    $discountedUnitPrice,
                    $promotion->getPercentageDiscount()
                );
            }

            if ($discountedUnitPrice !== $unitPrice) {
                $effectivePercent = $this->discountCalculator->calculateEffectivePercentage($unitPrice, $discountedUnitPrice);
                $item->setDiscount($effectivePercent);
            } else {
                $item->setDiscount(null);
            }

            $item->setDiscountValue($unitPrice - $discountedUnitPrice);
            $item->setDiscountedUnitPrice($discountedUnitPrice);
            $item->setDistributedOrderDiscountValue(0);

            $itemsTotalAfterItemDiscounts += $discountedUnitPrice * $item->getQuantity();
        }

        return $itemsTotalAfterItemDiscounts;
    }

    /**
     * Get item promotions sorted by position.
     *
     * @param Order $order
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

    /**
     * Check if a promotion is applicable to a specific order item.
     *
     * @param Promotion $promotion
     * @param OrderItem $item
     * @return bool
     */
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
}
