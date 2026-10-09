<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\RenameFolder\RenameFolderCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RenameFolderController extends BaseApiController
{
    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(
            new RenameFolderCommand(
                id: $id,
                name: $body['name'],
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
                "name": {
                  "type": "string",
                  "minLength": 1,
                  "maxLength": 255
                }
              },
              "required": ["name"]
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
