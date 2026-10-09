<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UploadAttachmentController extends AbstractUploadAttachmentController
{
    public function __invoke(string $folderId, Request $request): JsonResponse
    {
        return $this->upload($request, folderId: $folderId, noteId: null);
    }
}
