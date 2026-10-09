<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\CreateNote\CreateNoteCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\InvalidContentLocation;
use App\Notes\Domain\Exception\ParentNoteNotFound;
use App\Notes\Domain\Exception\InvalidTag;
use App\Notes\Domain\ValueObject\NoteId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateNoteController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);
        $id = NoteId::random()->value();

        $this->dispatchTransactional(
            new CreateNoteCommand(
                id: $id,
                title: $body['title'],
                content: $body['content'] ?? '',
                folderId: $body['folder_id'] ?? null,
                parentNoteId: $body['parent_note_id'] ?? null,
                ownerId: $this->currentUserId(),
                pinned: (bool) ($body['pinned'] ?? false),
                tags: $body['tags'] ?? []
            )
        );

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    private function getValidationSchema(): string
    {
        return <<<'JSON'
            {
              "type": "array",
              "properties": {
                "title": {
                  "type": "string",
                  "minLength": 1,
                  "maxLength": 255
                },
                "content": {
                  "type": "string"
                },
                "folder_id": {
                  "type": ["string", "null"],
                  "format": "uuid"
                },
                "parent_note_id": {
                  "type": ["string", "null"],
                  "format": "uuid"
                },
                "pinned": {
                  "type": "boolean"
                },
                "tags": {
                  "type": "array",
                  "items": {"type": "string"}
                }
              },
              "required": ["title"]
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            ParentNoteNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidContentLocation::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidTag::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
