<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\EmptyTrash\EmptyTrashCommand;
use Symfony\Component\HttpFoundation\JsonResponse;

class EmptyTrashController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        $this->dispatchTransactional(
            new EmptyTrashCommand(
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse([]);
    }

    protected function exceptions(): array
    {
        return [];
    }
}
