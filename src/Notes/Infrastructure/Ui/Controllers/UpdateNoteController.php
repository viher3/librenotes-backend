<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\UpdateNote\UpdateNoteCommand;
use App\Core\Domain\Exception\InvalidPartialUpdateFieldsException;
use App\Notes\Domain\Exception\InvalidIcon;
use App\Notes\Domain\Exception\InvalidTag;
use App\Notes\Domain\Exception\NoteNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateNoteController extends BaseApiController
{
    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(
            new UpdateNoteCommand(
                id: $id,
                fields: $body,
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
                "title": {
                  "type": "string",
                  "minLength": 1,
                  "maxLength": 255
                },
                "icon": {"type": ["string", "null"], "maxLength": 32},
                "content": {
                  "type": "string"
                },
                "pinned": {
                  "type": "boolean"
                },
                "tags": {
                  "type": "array",
                  "items": {"type": "string"}
                }
              }
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            NoteNotFound::class => Response::HTTP_NOT_FOUND,
            InvalidTag::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidIcon::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidPartialUpdateFieldsException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
