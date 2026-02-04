<?php

declare(strict_types=1);

namespace App\Component\Cart\Service;

use App\Component\Order\Entity\Order;
use App\Component\OrderItem\Entity\OrderItem;
use App\Component\Product\Entity\Product;
use DomainException;

class CartService
{
    public const MIN_TOTAL_QUANTITY = 1;
    public const MAX_TOTAL_QUANTITY = 50;
    public const MAX_DIFFERENT_PRODUCTS = 5;
    public const MIN_ITEM_QUANTITY = 1;
    public const MAX_ITEM_QUANTITY = 20;

    public function addProduct(Order $cart, Product $product, int $quantity): void
    {
        if ($quantity < self::MIN_ITEM_QUANTITY) {
            throw new DomainException('Quantity must be at least 1.');
        }

        $existingItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct() === $product) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem === null && $cart->getItems()->count() >= self::MAX_DIFFERENT_PRODUCTS) {
            throw new DomainException(sprintf('Cart cannot contain more than %d different products.', self::MAX_DIFFERENT_PRODUCTS));
        }

        $newItemQuantity = $quantity;
        if ($existingItem !== null) {
            $newItemQuantity = $existingItem->getQuantity() + $quantity;
        }

        if ($newItemQuantity > self::MAX_ITEM_QUANTITY) {
            throw new DomainException(sprintf('Quantity of a single product in cart cannot exceed %d.', self::MAX_ITEM_QUANTITY));
        }

        $currentTotalQuantity = 0;
        foreach ($cart->getItems() as $item) {
            $currentTotalQuantity += $item->getQuantity();
        }

        if ($currentTotalQuantity + $quantity > self::MAX_TOTAL_QUANTITY) {
            throw new DomainException(sprintf('Total quantity in cart cannot exceed %d.', self::MAX_TOTAL_QUANTITY));
        }

        if ($existingItem !== null) {
            $existingItem->setQuantity($newItemQuantity);
            $existingItem->recalculateTotal();
        } else {
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($newItemQuantity);
            $orderItem->setUnitPrice($product->getPrice());
            $orderItem->recalculateTotal();
            $cart->addItem($orderItem);
        }
    }
}
