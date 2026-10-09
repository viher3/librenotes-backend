<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\CreateLink\CreateLinkCommand;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\InvalidLinkUrl;
use App\Notes\Domain\Exception\InvalidTag;
use App\Notes\Domain\ValueObject\LinkId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateLinkController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);
        $id = LinkId::random()->value();

        $this->dispatchTransactional(
            new CreateLinkCommand(
                id: $id,
                title: $body['title'],
                url: $body['url'],
                note: $body['note'] ?? null,
                folderId: $body['folder_id'] ?? null,
                ownerId: $this->currentUserId(),
                tags: $body['tags'] ?? []
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
                "title": {"type": "string", "minLength": 1, "maxLength": 255},
                "url": {"type": "string", "minLength": 1, "maxLength": 2048},
                "note": {"type": ["string", "null"]},
                "folder_id": {"type": ["string", "null"], "format": "uuid"},
                "tags": {"type": "array", "items": {"type": "string"}}
              },
              "required": ["title", "url"]
            }
            JSON;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            FolderNotFound::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidLinkUrl::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidTag::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
