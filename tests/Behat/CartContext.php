<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use Behat\Behat\Context\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Webmozart\Assert\Assert;

final class CartContext implements Context
{
    private Response $response;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly RouterInterface $router,
    ) {
    }

    /**
     * @When a user adds product :productCode with quantity :quantity to cart
     */
    public function aUserAddsProductWithQuantityToCart(string $productCode, int $quantity): void
    {
        $path = $this->router->generate('cart_add');
        $this->response = $this->kernel->handle(Request::create(
            $path,
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['product_code' => $productCode, 'quantity' => $quantity], JSON_THROW_ON_ERROR)
        ));
    }

    /**
     * @Then the cart response should have status code :code
     */
    public function theCartResponseShouldHaveStatusCode(int $code): void
    {
        Assert::same($this->response->getStatusCode(), $code);
    }

    /**
     * @Then the cart response should contain message :message
     */
    public function theCartResponseShouldContainMessage(string $message): void
    {
        $content = json_decode($this->response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        Assert::same($content['message'] ?? $content['error'] ?? '', $message);
    }
}
