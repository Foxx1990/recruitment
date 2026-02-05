<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class OpenApiController
{
    #[Route('/openapi', name: 'openapi_spec', methods: ['GET'])]
    public function spec(): Response
    {
        $file = __DIR__ . '/../../public/openapi.yaml';
        if (!file_exists($file)) {
            return new JsonResponse(['error' => 'OpenAPI spec not found'], Response::HTTP_NOT_FOUND);
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return new JsonResponse(['error' => 'Failed to read OpenAPI spec'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response($content, Response::HTTP_OK, ['Content-Type' => 'application/yaml']);
    }
}
