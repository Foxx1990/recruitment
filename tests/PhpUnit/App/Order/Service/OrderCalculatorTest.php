<?php

declare(strict_types=1);

namespace App\Tests\PhpUnit\App\Order\Service;

use App\Component\Order\Entity\Order;
use App\Component\Order\Service\ItemDiscountApplicator;
use App\Component\Order\Service\OrderCalculator;
use App\Component\Order\Service\OrderDiscountDistributor;
use App\Component\Order\Service\PercentageDiscountCalculator;
use App\Component\Order\Service\TaxCalculator;
use App\Component\OrderItem\Entity\OrderItem;
use App\Component\OrderPromotion\Entity\OrderPromotion;
use App\Component\Product\Entity\Product;
use App\Component\Promotion\Entity\Promotion;
use PHPUnit\Framework\TestCase;

final class OrderCalculatorTest extends TestCase
{
    private OrderCalculator $calculator;

    protected function setUp(): void
    {
        $discountCalculator = new PercentageDiscountCalculator();
        $itemDiscountApplicator = new ItemDiscountApplicator($discountCalculator);
        $discountDistributor = new OrderDiscountDistributor();
        $taxCalculator = new TaxCalculator();
        
        $this->calculator = new OrderCalculator(
            $discountCalculator,
            $itemDiscountApplicator,
            $discountDistributor,
            $taxCalculator
        );
    }

    public function testItemPromotionIsAppliedOnlyWhenTypeMatchesFilter(): void
    {
        $product = $this->createProduct('P1', Product::TYPE_AUDIO, 100, null);
        $item = $this->createItem($product, 2);

        $order = new Order();
        $order->addItem($item);

        $promotion = new Promotion();
        $promotion->setType(Promotion::TYPE_ITEM);
        $promotion->setPercentageDiscount(10);
        $promotion->setProductTypesFilter([Product::TYPE_BOOK]);

        $orderPromotion = new OrderPromotion();
        $orderPromotion->setPromotion($promotion);
        $orderPromotion->setPosition(1);
        $order->addItemPromotion($orderPromotion);

        $this->calculator->recalculate($order);

        $this->assertNull($item->getDiscount());
        $this->assertSame(0, $item->getDiscountValue());
        $this->assertSame(100, $item->getDiscountedUnitPrice());
        $this->assertSame(0, $item->getDistributedOrderDiscountValue());
        $this->assertSame(200, $item->getTotal());

        $this->assertSame(200, $order->getItemsTotal());
        $this->assertSame(0, $order->getAdjustmentsTotal());
        $this->assertSame(200, $order->getTotal());
    }

    public function testMultipleItemPromotionsAreAppliedInOrderTheyWereAdded(): void
    {
        $product = $this->createProduct('P1', Product::TYPE_AUDIO, 100, null);
        $item = $this->createItem($product, 1);

        $order = new Order();
        $order->addItem($item);

        $promo1 = new Promotion();
        $promo1->setType(Promotion::TYPE_ITEM);
        $promo1->setPercentageDiscount(50);
        $promo1->setProductTypesFilter([Product::TYPE_AUDIO]);

        $promo2 = new Promotion();
        $promo2->setType(Promotion::TYPE_ITEM);
        $promo2->setPercentageDiscount(10);
        $promo2->setProductTypesFilter([Product::TYPE_AUDIO]);

        $op1 = new OrderPromotion();
        $op1->setPromotion($promo1);
        $op1->setPosition(1);
        $order->addItemPromotion($op1);

        $op2 = new OrderPromotion();
        $op2->setPromotion($promo2);
        $op2->setPosition(2);
        $order->addItemPromotion($op2);

        $this->calculator->recalculate($order);

        $this->assertSame(55, $item->getDiscount());
        $this->assertSame(55, $item->getDiscountValue());
        $this->assertSame(45, $item->getDiscountedUnitPrice());
        $this->assertSame(45, $item->getTotal());

        $this->assertSame(45, $order->getItemsTotal());
        $this->assertSame(0, $order->getAdjustmentsTotal());
        $this->assertSame(45, $order->getTotal());
    }

    public function testOrderPromotionIsAppliedAfterItemPromotionsAndDistributedAcrossItems(): void
    {
        $product1 = $this->createProduct('P1', Product::TYPE_BOOK, 100, 23);
        $product2 = $this->createProduct('P2', Product::TYPE_BOOK, 100, 23);

        $item1 = $this->createItem($product1, 1);
        $item2 = $this->createItem($product2, 1);

        $order = new Order();
        $order->addItem($item1);
        $order->addItem($item2);

        $orderPromo = new Promotion();
        $orderPromo->setType(Promotion::TYPE_ORDER);
        $orderPromo->setPercentageDiscount(10);
        $order->setOrderPromotion($orderPromo);

        $this->calculator->recalculate($order);

        $this->assertSame(200, $order->getItemsTotal());
        $this->assertSame(-20, $order->getAdjustmentsTotal());
        $this->assertSame(180, $order->getTotal()); // 200 - 20 (no tax)

        $this->assertSame(10, $item1->getDistributedOrderDiscountValue());
        $this->assertSame(90, $item1->getDiscountedUnitPrice());
        $this->assertSame(90, $item1->getTotal());

        $this->assertSame(10, $item2->getDistributedOrderDiscountValue());
        $this->assertSame(90, $item2->getDiscountedUnitPrice());
        $this->assertSame(90, $item2->getTotal());

        $this->assertSame(32, $order->getTaxTotal());
        $this->assertSame(16, $item1->getTaxValue());
        $this->assertSame(16, $item2->getTaxValue());
    }

    private function createProduct(string $code, string $type, int $price, ?int $taxRate): Product
    {
        $product = new Product();
        $product->setCode($code);
        $product->setType($type);
        $product->setPrice($price);
        $product->setTaxRate($taxRate);

        return $product;
    }

    private function createItem(Product $product, int $quantity): OrderItem
    {
        $item = new OrderItem();
        $item->setProduct($product);
        $item->setQuantity($quantity);
        $item->setUnitPrice($product->getPrice());

        return $item;
    }
}
