<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\RestoreFolder\RestoreFolderCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RestoreFolderController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        $this->dispatchTransactional(
            new RestoreFolderCommand(
                id: $id,
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse([]);
    }

    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
            NotInTrash::class => Response::HTTP_CONFLICT,
        ];
    }
}
