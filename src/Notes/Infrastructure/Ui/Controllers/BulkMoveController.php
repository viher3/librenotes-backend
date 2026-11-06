<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Notes\Application\BulkMoveItems\BulkMoveItemsCommand;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Exception\DestinationFolderNotFound;
use App\Notes\Domain\Exception\FolderCycleDetected;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\InvalidAttachmentLocation;
use App\Notes\Domain\Exception\InvalidContentLocation;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Exception\NoteCycleDetected;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Exception\ParentNoteNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** Moves the selected folders, notes, links and files to one destination, all or nothing. */
class BulkMoveController extends AbstractBulkItemsController
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $response = $this->dispatchTransactional(
            new BulkMoveItemsCommand(
                items: $this->itemRefs($body['items']),
                folderId: $body['folder_id'] ?? null,
                parentNoteId: $body['parent_note_id'] ?? null,
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse(['data' => $response?->getResult() ?? []]);
    }

    private function getValidationSchema(): string
    {
        return '{"type": "array", "properties": {"items": ' . self::ITEMS_SCHEMA . ', '
            . '"folder_id": {"type": ["string", "null"], "format": "uuid"}, '
            . '"parent_note_id": {"type": ["string", "null"], "format": "uuid"}}, "required": ["items"]}';
    }

    /**
     * An item that is missing is a 404; a destination that is missing, or a placement that is not allowed, is a 422.
     *
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
            NoteNotFound::class => Response::HTTP_NOT_FOUND,
            LinkNotFound::class => Response::HTTP_NOT_FOUND,
            AttachmentNotFound::class => Response::HTTP_NOT_FOUND,
            DestinationFolderNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            ParentNoteNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidContentLocation::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidAttachmentLocation::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            FolderCycleDetected::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            NoteCycleDetected::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
