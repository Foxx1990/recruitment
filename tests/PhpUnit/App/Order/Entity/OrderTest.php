<?php

namespace App\Tests\PhpUnit\App\Order\Entity;

use App\Component\Order\Entity\Order;
use App\Component\OrderItem\Entity\OrderItem;
use PHPUnit\Framework\TestCase;

/**
 * For example purposes only (can be removed)
 */
class OrderTest extends TestCase
{
    public function testOrderTotalCalculation(): void
    {
        $orderItem = new OrderItem();
        $orderItem->setQuantity(2);
        $orderItem->setUnitPrice(99);
        $orderItem->setTotal($orderItem->getSubtotal());

        $order = new Order();
        $order->addItem($orderItem);

        $this->assertSame(198, $order->getItemsTotal());
    }
}
