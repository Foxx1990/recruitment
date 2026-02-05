<?php

declare(strict_types=1);

namespace App\Tests\PhpUnit\App\Cart\Service;

use App\Component\Cart\Service\CartService;
use App\Component\Cart\Service\CartValidator;
use App\Component\Order\Entity\Order;
use App\Component\Product\Entity\Product;
use DomainException;
use PHPUnit\Framework\TestCase;

class CartServiceTest extends TestCase
{
    private CartService $cartService;

    protected function setUp(): void
    {
        $this->cartService = new CartService(new CartValidator());
    }

    public function testAddProductIncreasesQuantityOfExistingItem(): void
    {
        $cart = new Order();
        $product = $this->createProduct('P1', 100);

        $this->cartService->addProduct($cart, $product, 2);
        $this->cartService->addProduct($cart, $product, 3);

        $this->assertCount(1, $cart->getItems());
        $this->assertSame(5, $cart->getItems()->first()->getQuantity());
    }

    public function testAddProductThrowsExceptionWhenMaxDifferentProductsExceeded(): void
    {
        $cart = new Order();
        for ($i = 1; $i <= 5; $i++) {
            $this->cartService->addProduct($cart, $this->createProduct("P$i", 100), 1);
        }

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cart cannot contain more than 5 different products.');

        $this->cartService->addProduct($cart, $this->createProduct("P6", 100), 1);
    }

    public function testAddProductThrowsExceptionWhenMaxTotalQuantityExceeded(): void
    {
        $cart = new Order();
        $this->cartService->addProduct($cart, $this->createProduct('P1', 100), 20);
        $this->cartService->addProduct($cart, $this->createProduct('P2', 100), 20);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Total quantity in cart cannot exceed 50.');

        $this->cartService->addProduct($cart, $this->createProduct('P3', 100), 11);
    }

    public function testAddProductThrowsExceptionWhenAddingSmallQuantityExceedsMaxTotal(): void
    {
        $cart = new Order();
        $this->cartService->addProduct($cart, $this->createProduct('P1', 100), 20);
        $this->cartService->addProduct($cart, $this->createProduct('P2', 100), 20);
        $this->cartService->addProduct($cart, $this->createProduct('P3', 100), 10);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Total quantity in cart cannot exceed 50.');

        $this->cartService->addProduct($cart, $this->createProduct('P4', 100), 1);
    }

    public function testAddProductThrowsExceptionWhenMaxItemQuantityExceeded(): void
    {
        $cart = new Order();
        $product = $this->createProduct('P1', 100);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Quantity of a single product in cart cannot exceed 20.');

        $this->cartService->addProduct($cart, $product, 21);
    }

    public function testAddProductThrowsExceptionWhenMaxItemQuantityExceededForExistingItem(): void
    {
        $cart = new Order();
        $product = $this->createProduct('P1', 100);

        $this->cartService->addProduct($cart, $product, 15);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Quantity of a single product in cart cannot exceed 20.');

        $this->cartService->addProduct($cart, $product, 6);
    }

    public function testAddProductThrowsExceptionOnInvalidQuantity(): void
    {
        $cart = new Order();
        $product = $this->createProduct('P1', 100);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Quantity must be at least 1.');

        $this->cartService->addProduct($cart, $product, 0);
    }

    private function createProduct(string $code, int $price): Product
    {
        $product = new Product();
        $product->setCode($code);
        $product->setPrice($price);
        return $product;
    }
}
