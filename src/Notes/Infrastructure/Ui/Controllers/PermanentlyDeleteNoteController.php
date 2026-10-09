<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\PermanentlyDeleteNote\PermanentlyDeleteNoteCommand;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PermanentlyDeleteNoteController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        $this->dispatchTransactional(
            new PermanentlyDeleteNoteCommand(
                id: $id,
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse([]);
    }

    protected function exceptions(): array
    {
        return [
            NoteNotFound::class => Response::HTTP_NOT_FOUND,
            NotInTrash::class => Response::HTTP_CONFLICT,
        ];
    }
}
