<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\MoveFolder\MoveFolderCommand;
use App\Notes\Domain\Exception\FolderCycleDetected;
use App\Notes\Domain\Exception\FolderNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MoveFolderController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(
            new MoveFolderCommand(
                id: $id,
                parentFolderId: $body['parent_folder_id'] ?? null,
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID
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
                "parent_folder_id": {
                  "type": ["string", "null"],
                  "format": "uuid"
                }
              }
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
            FolderCycleDetected::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
