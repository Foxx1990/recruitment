<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

final class PercentageDiscountCalculator implements DiscountCalculatorInterface
{
    /**
     * Calculate the final amount after applying a percentage discount.
     *
     * @param int $amount The original amount in cents
     * @param int $percentage The discount percentage (0-100)
     * @return int The amount after discount in cents
     */
    public function calculate(int $amount, int $percentage): int
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

    /**
     * Calculate the effective discount percentage between original and discounted prices.
     *
     * @param int $originalUnitPrice Original price in cents
     * @param int $discountedUnitPrice Discounted price in cents
     * @return int Effective discount percentage
     */
    public function calculateEffectivePercentage(int $originalUnitPrice, int $discountedUnitPrice): int
    {
        if ($originalUnitPrice <= 0) {
            return 0;
        }

        $discountValue = $originalUnitPrice - $discountedUnitPrice;

        return (int) floor(($discountValue * 100) / $originalUnitPrice);
    }
}
