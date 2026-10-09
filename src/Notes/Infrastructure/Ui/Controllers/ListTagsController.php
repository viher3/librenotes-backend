<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\ListTags\ListTagsQuery;
use App\Notes\Application\ListTags\ListTagsResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class ListTagsController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        /** @var ListTagsResponse $response */
        $response = $this->ask(new ListTagsQuery($this->currentUserId()));

        return new JsonResponse($response->getResult());
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [];
    }
}
