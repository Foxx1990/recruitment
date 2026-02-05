<?php

declare(strict_types=1);

namespace App\Component\Cart\Service;

use App\Component\Order\Entity\Order;
use App\Component\OrderItem\Entity\OrderItem;
use DomainException;

final class CartValidator
{
    public function assertMinQuantity(int $quantity, int $minQuantity): void
    {
        if ($quantity < $minQuantity) {
            throw new DomainException('Quantity must be at least 1.');
        }
    }

    public function assertDifferentProductsLimit(Order $cart, ?OrderItem $existingItem, int $maxDifferentProducts): void
    {
        if ($existingItem === null && $cart->getItems()->count() >= $maxDifferentProducts) {
            throw new DomainException(sprintf('Cart cannot contain more than %d different products.', $maxDifferentProducts));
        }
    }

    public function assertMaxItemQuantity(int $newItemQuantity, int $maxItemQuantity): void
    {
        if ($newItemQuantity > $maxItemQuantity) {
            throw new DomainException(sprintf('Quantity of a single product in cart cannot exceed %d.', $maxItemQuantity));
        }
    }

    public function assertMaxTotalQuantity(Order $cart, int $quantity, int $maxTotalQuantity): void
    {
        $currentTotalQuantity = 0;
        foreach ($cart->getItems() as $item) {
            $currentTotalQuantity += $item->getQuantity();
        }

        if ($currentTotalQuantity + $quantity > $maxTotalQuantity) {
            throw new DomainException(sprintf('Total quantity in cart cannot exceed %d.', $maxTotalQuantity));
        }
    }
}
