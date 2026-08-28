<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\EmptyTrash\EmptyTrashCommand;
use Symfony\Component\HttpFoundation\JsonResponse;

class EmptyTrashController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(): JsonResponse
    {
        $this->dispatchTransactional(
            new EmptyTrashCommand(
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID
            )
        );

        return new JsonResponse([]);
    }

    protected function exceptions(): array
    {
        return [];
    }
}
