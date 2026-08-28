<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Ui\Controllers;

use App\Configuration\Application\Search\ConfigurationSearchQuery;
use App\Configuration\Application\Search\ConfigurationSearchResponse;
use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ConfigurationSearchController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $params = $this->getParams($request);

        /** @var ConfigurationSearchResponse $response */
        $response = $this->ask(new ConfigurationSearchQuery(
            $this->getFilters($request),
            ...$this->getSearchParams($params)
        ));

        return new JsonResponse($response->getResult());
    }

    protected function exceptions(): array
    {
        return [];
    }
}
