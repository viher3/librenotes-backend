<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\ListTrash\ListTrashQuery;
use App\Notes\Application\ListTrash\ListTrashResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class ListTrashController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(): JsonResponse
    {
        /** @var ListTrashResponse $response */
        $response = $this->ask(
            new ListTrashQuery(
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID
            )
        );

        return new JsonResponse(
            data: $response->getResult(),
            status: 200
        );
    }

    protected function exceptions(): array
    {
        return [];
    }
}
