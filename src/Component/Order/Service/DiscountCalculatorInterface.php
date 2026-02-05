<?php

declare(strict_types=1);

namespace App\Component\Order\Service;

interface DiscountCalculatorInterface
{
    /**
     * Calculate the final amount after applying a percentage discount.
     *
     * @param int $amount The original amount in cents
     * @param int $percentage The discount percentage (0-100)
     * @return int The amount after discount in cents
     */
    public function calculate(int $amount, int $percentage): int;
}
