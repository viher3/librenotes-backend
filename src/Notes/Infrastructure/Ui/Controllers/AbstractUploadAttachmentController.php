<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\UploadAttachment\UploadAttachmentCommand;
use App\Notes\Domain\Exception\AttachmentEmpty;
use App\Notes\Domain\Exception\AttachmentTooLarge;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\InvalidAttachmentLocation;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\ValueObject\AttachmentId;
use App\Notes\Domain\ValueObject\FileSizeBytes;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractUploadAttachmentController extends BaseApiController
{
    protected function upload(Request $request, ?string $folderId, ?string $noteId): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            return new JsonResponse(
                ['errors' => ['[file] A file must be sent as multipart/form-data']],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$file->isValid()) {
            // PHP rejected the upload before it reached us (size limits, partial upload...).
            if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                throw new AttachmentTooLarge(FileSizeBytes::MAX_ATTACHMENT_BYTES);
            }

            return new JsonResponse(
                ['errors' => ['[file] The upload failed: ' . $file->getErrorMessage()]],
                Response::HTTP_BAD_REQUEST
            );
        }

        $id = AttachmentId::random()->value();

        $this->dispatchTransactional(
            new UploadAttachmentCommand(
                id: $id,
                folderId: $folderId,
                noteId: $noteId,
                ownerId: $this->currentUserId(),
                originalFileName: $file->getClientOriginalName(),
                mimeType: $file->getMimeType() ?? 'application/octet-stream',
                sizeBytes: $file->getSize(),
                tmpFilePath: $file->getPathname()
            )
        );

        return new JsonResponse(['id' => $id], Response::HTTP_CREATED);
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
            NoteNotFound::class => Response::HTTP_NOT_FOUND,
            AttachmentTooLarge::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            AttachmentEmpty::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidAttachmentLocation::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
