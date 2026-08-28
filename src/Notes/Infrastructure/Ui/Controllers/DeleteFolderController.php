<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\MoveFolderToTrash\MoveFolderToTrashCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft-delete: moves the folder (and everything it contains) to the trash.
 * See PermanentlyDeleteFolderController for the irreversible action.
 */
class DeleteFolderController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $id): JsonResponse
    {
        $this->dispatchTransactional(
            new MoveFolderToTrashCommand(
                id: $id,
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID
            )
        );

        return new JsonResponse([]);
    }

    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
