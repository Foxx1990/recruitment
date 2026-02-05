<?php

declare(strict_types=1);

namespace App\Component\OrderItem\Entity;

use App\Component\Order\Entity\Order;
use App\Component\Product\Entity\Product;

class OrderItem
{
    protected int $id;
    protected ?Order $order;
    protected ?Product $product;
    protected int $quantity;
    protected int $unitPrice;
    protected ?int $discount = null;
    protected int $discountValue = 0;
    protected int $distributedOrderDiscountValue = 0;
    protected int $discountedUnitPrice = 0;
    protected ?int $taxValue;
    protected int $total = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): void
    {
        $this->product = $product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    public function getDiscount(): ?int
    {
        return $this->discount;
    }

    public function setDiscount(?int $discount): void
    {
        $this->discount = $discount;
    }

    public function getDiscountValue(): int
    {
        return $this->discountValue;
    }

    public function setDiscountValue(int $discountValue): void
    {
        $this->discountValue = $discountValue;
    }

    public function getDistributedOrderDiscountValue(): int
    {
        return $this->distributedOrderDiscountValue;
    }

    public function setDistributedOrderDiscountValue(int $distributedOrderDiscountValue): void
    {
        $this->distributedOrderDiscountValue = $distributedOrderDiscountValue;
    }

    public function getDiscountedUnitPrice(): int
    {
        return $this->discountedUnitPrice;
    }

    public function setDiscountedUnitPrice(int $discountedUnitPrice): void
    {
        $this->discountedUnitPrice = $discountedUnitPrice;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getSubtotal(): int
    {
        return $this->unitPrice * $this->quantity;
    }

    public function getTaxValue(): ?int
    {
        return $this->taxValue;
    }

    public function setTaxValue(?int $taxValue): void
    {
        $this->taxValue = $taxValue;
    }
}
