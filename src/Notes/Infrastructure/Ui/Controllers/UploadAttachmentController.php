<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\UploadAttachment\UploadAttachmentCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\ValueObject\AttachmentId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UploadAttachmentController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $folderId, Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if ($file === null) {
            return new JsonResponse(
                ['errors' => ['[file] A file must be sent as multipart/form-data']],
                Response::HTTP_BAD_REQUEST
            );
        }

        $id = AttachmentId::random()->value();

        $this->dispatchTransactional(
            new UploadAttachmentCommand(
                id: $id,
                folderId: $folderId,
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID,
                originalFileName: $file->getClientOriginalName(),
                mimeType: $file->getMimeType() ?? 'application/octet-stream',
                sizeBytes: $file->getSize() ?? 0,
                tmpFilePath: $file->getPathname()
            )
        );

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
