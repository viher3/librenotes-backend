<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Domain\Exception\InvalidPartialUpdateFieldsException;
use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\UpdateFolder\UpdateFolderCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\InvalidIcon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateFolderController extends BaseApiController
{
    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $this->dispatchTransactional(
            new UpdateFolderCommand(
                id: $id,
                fields: $this->getParams($request),
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
                },
                "icon": {"type": ["string", "null"], "maxLength": 32}
              }
            }
            JSON;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_NOT_FOUND,
            InvalidIcon::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidPartialUpdateFieldsException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
