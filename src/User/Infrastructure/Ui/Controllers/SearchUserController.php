<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\Search\SearchUserQuery;
use App\User\Application\Search\SearchUserResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SearchUserController extends BaseApiController
{
    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request) : JsonResponse
    {
        $params = $this->getParams($request);

        /** @var SearchUserResponse $searchResponse */
        $searchResponse = $this->ask(
            new SearchUserQuery(
                $this->getFilters($request),
                ...$this->getSearchParams($params)
            )
        );

        return new JsonResponse(
            data: $searchResponse->getResult(),
            status: 200
        );
    }

    protected function exceptions(): array
    {
        return [];
    }
}
