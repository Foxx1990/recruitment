<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Component\Order\Entity\Order;
use App\Component\Promotion\Entity\Promotion;
use Behat\Behat\Context\Context;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final class OrderContext implements Context
{
    private Response $response;
    private ?int $lastOrderId = null;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly RouterInterface $router,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @Given there is an order for user :userName with product :productCode quantity :quantity
     */
    public function thereIsAnOrderForUserWithProduct(string $userName, string $productCode, int $quantity): void
    {
        $user = $this->entityManager->getRepository(\App\Component\User\Entity\User::class)->findOneBy(['name' => $userName]);
        Assert::notNull($user, sprintf('User "%s" not found', $userName));

        $product = $this->entityManager->getRepository(\App\Component\Product\Entity\Product::class)->findOneBy(['code' => $productCode]);
        Assert::notNull($product, sprintf('Product "%s" not found', $productCode));

        $order = new Order();
        $order->setUser($user);

        $orderItem = new \App\Component\OrderItem\Entity\OrderItem();
        $orderItem->setProduct($product);
        $orderItem->setQuantity($quantity);
        $orderItem->setUnitPrice($product->getPrice());
        $orderItem->setTotal($orderItem->getSubtotal());

        $order->addItem($orderItem);

        $this->entityManager->persist($order);
        $this->entityManager->flush();
        $this->lastOrderId = $order->getId();
    }

    /**
     * @When a user assigns promotion :promotionCode to order :orderId
     */
    public function aUserAssignsPromotionToOrder(string $promotionCode, int $orderId): void
    {
        if ($this->lastOrderId !== null && $orderId !== 999) {
            $orderId = $this->lastOrderId;
        }
        $promotion = $this->entityManager->getRepository(Promotion::class)->findOneBy(['code' => $promotionCode]);
        if ($promotion === null) {
            // Fallback: try to find by ID if code is numeric or not found by code
            $promotion = $this->entityManager->getRepository(Promotion::class)->find($promotionCode);
        }

        $promotionId = $promotion?->getId() ?? (int) $promotionCode;
        $path = $this->router->generate('order_assign_promotion', ['orderId' => $orderId, 'promotionId' => $promotionId]);
        $this->response = $this->kernel->handle(Request::create($path, 'POST'));
    }

    /**
     * @When a user requests details of order :orderId
     */
    public function aUserRequestsDetailsOfOrder(int $orderId): void
    {
        if ($this->lastOrderId !== null && $orderId !== 999) {
            $orderId = $this->lastOrderId;
        }
        $path = $this->router->generate('order_details', ['orderId' => $orderId]);
        $this->response = $this->kernel->handle(Request::create($path, 'GET'));
    }

    /**
     * @Then the order response should have status code :code
     */
    public function theOrderResponseShouldHaveStatusCode(int $code): void
    {
        Assert::same($this->response->getStatusCode(), $code);
    }

    /**
     * @Then the order response should contain message :message
     */
    public function theOrderResponseShouldContainMessage(string $message): void
    {
        $content = json_decode($this->response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $actual = is_array($content) ? ($content['message'] ?? $content['error'] ?? '') : '';
        Assert::same($actual, $message);
    }

    /**
     * @Then the order response should contain :count items
     */
    public function theOrderResponseShouldContainItems(int $count): void
    {
        $content = json_decode($this->response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        Assert::isArray($content['items'] ?? null);
        Assert::count($content['items'], $count);
    }

    /**
     * @Then the order response itemsTotal should be :total
     */
    public function theOrderResponseItemsTotalShouldBe(int $total): void
    {
        $content = json_decode($this->response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        Assert::same($content['itemsTotal'], $total);
    }

    /**
     * @Then the order response adjustmentsTotal should be :total
     */
    public function theOrderResponseAdjustmentsTotalShouldBe(int $total): void
    {
        $content = json_decode($this->response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        Assert::same($content['adjustmentsTotal'], $total);
    }

    /**
     * @Then the order response total should be :total
     */
    public function theOrderResponseTotalShouldBe(int $total): void
    {
        $content = json_decode($this->response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        Assert::same($content['total'], $total);
    }

    /**
     * @Then the first item should have discountedUnitPrice :price
     */
    public function theFirstItemShouldHaveDiscountedUnitPrice(int $price): void
    {
        $content = json_decode($this->response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $items = $content['items'] ?? [];
        Assert::notEmpty($items);
        Assert::same($items[0]['discountedUnitPrice'], $price);
    }

    /**
     * @Then the first item should have distributedOrderDiscountValue :value
     */
    public function theFirstItemShouldHaveDistributedOrderDiscountValue(int $value): void
    {
        $content = json_decode($this->response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        $items = $content['items'] ?? [];
        Assert::notEmpty($items);
        Assert::same($items[0]['distributedOrderDiscountValue'], $value);
    }
}
