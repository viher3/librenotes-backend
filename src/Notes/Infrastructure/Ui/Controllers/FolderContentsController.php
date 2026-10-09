<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\FolderContents\FolderContentsQuery;
use App\Notes\Application\FolderContents\FolderContentsResponse;
use App\Notes\Domain\Exception\FolderNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FolderContentsController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $params = $this->getParams($request);

        /** @var FolderContentsResponse $response */
        $response = $this->ask(
            new FolderContentsQuery(
                folderId: $params['folder_id'] ?? null,
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse(
            data: $response->getResult(),
            status: 200
        );
    }

    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
