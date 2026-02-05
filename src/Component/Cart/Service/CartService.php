<?php

declare(strict_types=1);

namespace App\Component\Cart\Service;

use App\Component\Order\Entity\Order;
use App\Component\OrderItem\Entity\OrderItem;
use App\Component\Product\Entity\Product;

class CartService
{
    public const MIN_TOTAL_QUANTITY = 1;
    public const MAX_TOTAL_QUANTITY = 50;
    public const MAX_DIFFERENT_PRODUCTS = 5;
    public const MIN_ITEM_QUANTITY = 1;
    public const MAX_ITEM_QUANTITY = 20;

    public function __construct(
        private readonly CartValidator $validator,
    ) {
    }

    public function addProduct(Order $cart, Product $product, int $quantity): void
    {
        $this->validator->assertMinQuantity($quantity, self::MIN_ITEM_QUANTITY);

        $existingItem = $this->findExistingItem($cart, $product);
        $this->validator->assertDifferentProductsLimit($cart, $existingItem, self::MAX_DIFFERENT_PRODUCTS);

        $newItemQuantity = $this->calculateNewItemQuantity($existingItem, $quantity);
        $this->validator->assertMaxItemQuantity($newItemQuantity, self::MAX_ITEM_QUANTITY);
        $this->validator->assertMaxTotalQuantity($cart, $quantity, self::MAX_TOTAL_QUANTITY);

        $this->applyItemQuantity($cart, $product, $existingItem, $newItemQuantity);
    }

    private function findExistingItem(Order $cart, Product $product): ?OrderItem
    {
        foreach ($cart->getItems() as $item) {
            if ($item->getProduct() === $product) {
                return $item;
            }
        }

        return null;
    }


    private function calculateNewItemQuantity(?OrderItem $existingItem, int $quantity): int
    {
        return $existingItem !== null ? $existingItem->getQuantity() + $quantity : $quantity;
    }

    private function applyItemQuantity(Order $cart, Product $product, ?OrderItem $existingItem, int $newItemQuantity): void
    {
        if ($existingItem !== null) {
            $existingItem->setQuantity($newItemQuantity);
            $existingItem->setTotal($existingItem->getSubtotal());
            return;
        }

        $orderItem = new OrderItem();
        $orderItem->setProduct($product);
        $orderItem->setQuantity($newItemQuantity);
        $orderItem->setUnitPrice($product->getPrice());
        $orderItem->setTotal($orderItem->getSubtotal());
        $cart->addItem($orderItem);
    }
}
