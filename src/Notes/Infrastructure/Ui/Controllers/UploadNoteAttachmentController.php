<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UploadNoteAttachmentController extends AbstractUploadAttachmentController
{
    public function __invoke(string $noteId, Request $request): JsonResponse
    {
        return $this->upload($request, folderId: null, noteId: $noteId);
    }
}
