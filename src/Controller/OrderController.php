<?php

declare(strict_types=1);

namespace App\Controller;

use App\Component\Order\Entity\Order;
use App\Component\Order\Service\OrderCalculator;
use App\Component\OrderPromotion\Entity\OrderPromotion;
use App\Component\Promotion\Entity\Promotion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class OrderController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrderCalculator $orderCalculator,
    ) {
    }

    #[Route('/orders/{orderId<\\d+>}', name: 'order_details', methods: ['GET'])]
    public function details(int $orderId): JsonResponse
    {
        /** @var Order|null $order */
        $order = $this->entityManager->find(Order::class, $orderId);
        if ($order === null) {
            return new JsonResponse(['message' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        $this->orderCalculator->recalculate($order);

        $items = [];
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();

            $items[] = [
                'id' => $item->getId(),
                'product' => [
                    'code' => $product?->getCode() ?? '',
                    'name' => $product?->getName() ?? '',
                ],
                'unitPrice' => $item->getUnitPrice(),
                'discount' => $item->getDiscount(),
                'discountValue' => $item->getDiscountValue(),
                'distributedOrderDiscountValue' => $item->getDistributedOrderDiscountValue(),
                'discountedUnitPrice' => $item->getDiscountedUnitPrice(),
                'quantity' => $item->getQuantity(),
                'total' => $item->getTotal(),
                'taxValue' => $item->getTaxValue(),
            ];
        }

        return new JsonResponse([
            'id' => $order->getId(),
            'itemsTotal' => $order->getItemsTotal(),
            'adjustmentsTotal' => $order->getAdjustmentsTotal(),
            'taxTotal' => $order->getTaxTotal(),
            'total' => $order->getTotal(),
            'items' => $items,
        ]);
    }

    #[Route('/orders/{orderId<\\d+>}/promotions/{promotionId<\\d+>}', name: 'order_assign_promotion', methods: ['POST'])]
    #[Operation(
        summary: 'Assign a promotion to an order',
        tags: ['Order'],
        parameters: [
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'promotionId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Promotion assigned successfully.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 404, description: 'Order or Promotion not found.'),
            new OA\Response(response: 422, description: 'Unsupported promotion type.')
        ]
    )]
    public function assignPromotion(int $orderId, int $promotionId): JsonResponse
    {
        /** @var Order|null $order */
        $order = $this->entityManager->find(Order::class, $orderId);
        if ($order === null) {
            return new JsonResponse(['message' => 'Order not found.'], Response::HTTP_NOT_FOUND);
        }

        /** @var Promotion|null $promotion */
        $promotion = $this->entityManager->find(Promotion::class, $promotionId);
        if ($promotion === null) {
            return new JsonResponse(['message' => 'Promotion not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($promotion->getType() === Promotion::TYPE_ORDER) {
            $order->setOrderPromotion($promotion);
            $this->orderCalculator->recalculate($order);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Promotion assigned to order successfully.'], Response::HTTP_CREATED);
        }

        if ($promotion->getType() !== Promotion::TYPE_ITEM) {
            return new JsonResponse(['violations' => ['Unsupported promotion type.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        foreach ($order->getItemPromotions() as $existing) {
            if ($existing->getPromotion()->getId() === $promotion->getId()) {
                return new JsonResponse(['message' => 'Promotion already assigned to order.'], Response::HTTP_OK);
            }
        }

        $maxPosition = 0;
        foreach ($order->getItemPromotions() as $existing) {
            $maxPosition = max($maxPosition, $existing->getPosition());
        }

        $orderPromotion = new OrderPromotion();
        $orderPromotion->setPromotion($promotion);
        $orderPromotion->setPosition($maxPosition + 1);
        $order->addItemPromotion($orderPromotion);

        $this->entityManager->persist($orderPromotion);
        $this->orderCalculator->recalculate($order);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Promotion assigned to order successfully.'], Response::HTTP_CREATED);
    }
}
