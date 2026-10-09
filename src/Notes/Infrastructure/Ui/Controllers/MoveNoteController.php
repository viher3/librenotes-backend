<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\MoveNote\MoveNoteCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\NoteCycleDetected;
use App\Notes\Domain\Exception\InvalidContentLocation;
use App\Notes\Domain\Exception\ParentNoteNotFound;
use App\Notes\Domain\Exception\NoteNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MoveNoteController extends BaseApiController
{
    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(
            new MoveNoteCommand(
                id: $id,
                folderId: $body['folder_id'] ?? null,
                parentNoteId: $body['parent_note_id'] ?? null,
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse([]);
    }

    private function getValidationSchema(): string
    {
        return <<<'JSON'
            {
              "type": "array",
              "properties": {
                "folder_id": {
                  "type": ["string", "null"],
                  "format": "uuid"
                },
                "parent_note_id": {
                  "type": ["string", "null"],
                  "format": "uuid"
                }
              }
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            NoteNotFound::class => Response::HTTP_NOT_FOUND,
            FolderNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            ParentNoteNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidContentLocation::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            NoteCycleDetected::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
