<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\CreateFolder\CreateFolderCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\InvalidIcon;
use App\Notes\Domain\ValueObject\FolderId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateFolderController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);
        $id = FolderId::random()->value();

        $this->dispatchTransactional(
            new CreateFolderCommand(
                id: $id,
                name: $body['name'],
                parentFolderId: $body['parent_folder_id'] ?? null,
                ownerId: $this->currentUserId(),
                icon: $body['icon'] ?? null
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
                "name": {
                  "type": "string",
                  "minLength": 1,
                  "maxLength": 255
                },
                "parent_folder_id": {
                  "type": ["string", "null"],
                  "format": "uuid"
                },
                "icon": {"type": ["string", "null"], "maxLength": 32}
              },
              "required": ["name"]
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidIcon::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
