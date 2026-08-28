<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Ui\Controllers;

use App\Configuration\Application\History\ConfigurationHistoryQuery;
use App\Configuration\Application\History\ConfigurationHistoryResponse;
use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ConfigurationHistoryController extends BaseApiController
{
    public function __invoke(string $key, Request $request): JsonResponse
    {
        $params = $this->getParams($request);
        $page = !empty($params[self::SEARCH_PARAM_PAGE_NAME]) ? (int) $params[self::SEARCH_PARAM_PAGE_NAME] : 1;
        $size = !empty($params[self::SEARCH_PARAM_SIZE_NAME]) ? (int) $params[self::SEARCH_PARAM_SIZE_NAME] : 20;

        /** @var ConfigurationHistoryResponse $response */
        $response = $this->ask(new ConfigurationHistoryQuery($key, $page, $size));

        return new JsonResponse($response->getResult());
    }

    protected function exceptions(): array
    {
        return [];
    }
}
