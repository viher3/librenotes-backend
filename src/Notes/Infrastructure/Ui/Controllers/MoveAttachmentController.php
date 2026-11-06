<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\MoveAttachment\MoveAttachmentCommand;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\InvalidAttachmentLocation;
use App\Notes\Domain\Exception\InvalidContentLocation;
use App\Notes\Domain\Exception\ParentNoteNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MoveAttachmentController extends BaseApiController
{
    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(
            new MoveAttachmentCommand(
                id: $id,
                folderId: $body['folder_id'] ?? null,
                noteId: $body['note_id'] ?? null,
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
                "folder_id": {"type": ["string", "null"], "format": "uuid"},
                "note_id": {"type": ["string", "null"], "format": "uuid"}
              }
            }
            JSON;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            AttachmentNotFound::class => Response::HTTP_NOT_FOUND,
            FolderNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            ParentNoteNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidContentLocation::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidAttachmentLocation::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
