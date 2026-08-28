<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Ui\Controllers\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        return new JsonResponse([
            'app' => $this->getParameter('app.name'),
            'version' => $this->getParameter('app.version'),
        ]);
    }

    protected function exceptions(): array
    {
        return [];
    }
}
