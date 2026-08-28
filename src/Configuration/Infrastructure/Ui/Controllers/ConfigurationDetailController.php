<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Ui\Controllers;

use App\Configuration\Application\Detail\ConfigurationDetailQuery;
use App\Configuration\Application\Detail\ConfigurationDetailResponse;
use App\Configuration\Domain\Exception\ConfigurationNotFoundException;
use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ConfigurationDetailController extends BaseApiController
{
    public function __invoke(string $key): JsonResponse
    {
        /** @var ConfigurationDetailResponse $response */
        $response = $this->ask(new ConfigurationDetailQuery($key));

        return new JsonResponse($response->getResult());
    }

    protected function exceptions(): array
    {
        return [
            ConfigurationNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
