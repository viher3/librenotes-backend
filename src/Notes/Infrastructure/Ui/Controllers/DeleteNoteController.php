<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\DeleteNote\DeleteNoteCommand;
use App\Notes\Domain\Exception\NoteNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DeleteNoteController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $id): JsonResponse
    {
        $this->dispatch(
            new DeleteNoteCommand(
                id: $id,
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID
            )
        );

        return new JsonResponse([]);
    }

    protected function exceptions(): array
    {
        return [
            NoteNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
