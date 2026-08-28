<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\CreateNote\CreateNoteCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\ValueObject\NoteId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateNoteController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

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
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID
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
        ];
    }
}
