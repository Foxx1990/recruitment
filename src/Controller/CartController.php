<?php

declare(strict_types=1);

namespace App\Controller;

use App\Component\Cart\Service\CartService;
use App\Component\Order\Entity\Order;
use App\Component\Product\Entity\Product;
use App\Component\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CartController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CartService $cartService,
    ) {
    }

    #[Route('/cart/add', name: 'cart_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $productCode = $data['product_code'] ?? null;
        $quantity = $data['quantity'] ?? 1;

        if (!$productCode) {
            return new JsonResponse(['error' => 'Product code is required.'], Response::HTTP_BAD_REQUEST);
        }

        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['code' => $productCode]);

        if (!$product) {
            return new JsonResponse(['error' => sprintf('Product with code "%s" not found.', $productCode)], Response::HTTP_NOT_FOUND);
        }

        // For simplicity, we use the first available user as the cart owner
        $user = $this->entityManager->getRepository(User::class)->findOneBy([]);
        if (!$user) {
            return new JsonResponse(['error' => 'No user found in the system.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Find existing order for this user (acting as a cart)
        $order = $this->entityManager->getRepository(Order::class)->findOneBy(['user' => $user]);

        if (!$order) {
            $order = new Order();
            $order->setUser($user);
            $this->entityManager->persist($order);
        }

        try {
            $this->cartService->addProduct($order, $product, (int)$quantity);
            $this->entityManager->flush();
        } catch (DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['message' => 'Product added to cart successfully.'], Response::HTTP_CREATED);
    }
}
