<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\MoveLinkToTrash\MoveLinkToTrashCommand;
use App\Notes\Domain\Exception\LinkNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Soft-delete: moves the link to the trash. See PermanentlyDeleteLinkController for the irreversible action.
 */
class DeleteLinkController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        $this->dispatchTransactional(
            new MoveLinkToTrashCommand(
                id: $id,
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse([]);
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            LinkNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
