<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Notes\Application\BulkMoveItemsToTrash\BulkMoveItemsToTrashCommand;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Exception\NoteNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** Sends the selected folders, notes, links and files to the trash, all or nothing. */
class BulkTrashController extends AbstractBulkItemsController
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $response = $this->dispatchTransactional(
            new BulkMoveItemsToTrashCommand(
                items: $this->itemRefs($body['items']),
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse(['data' => $response?->getResult() ?? []]);
    }

    private function getValidationSchema(): string
    {
        return '{"type": "array", "properties": {"items": ' . self::ITEMS_SCHEMA . '}, "required": ["items"]}';
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
            NoteNotFound::class => Response::HTTP_NOT_FOUND,
            LinkNotFound::class => Response::HTTP_NOT_FOUND,
            AttachmentNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
