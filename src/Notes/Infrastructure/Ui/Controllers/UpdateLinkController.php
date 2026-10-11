<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Core\Domain\Exception\InvalidPartialUpdateFieldsException;
use App\Notes\Application\UpdateLink\UpdateLinkCommand;
use App\Notes\Domain\Exception\InvalidIcon;
use App\Notes\Domain\Exception\InvalidLinkUrl;
use App\Notes\Domain\Exception\InvalidTag;
use App\Notes\Domain\Exception\LinkNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLinkController extends BaseApiController
{
    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $this->dispatchTransactional(
            new UpdateLinkCommand(
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
                "title": {"type": "string", "minLength": 1, "maxLength": 255},
                "url": {"type": "string", "minLength": 1, "maxLength": 2048},
                "note": {"type": ["string", "null"]},
                "icon": {"type": ["string", "null"], "maxLength": 32},
                "tags": {"type": "array", "items": {"type": "string"}}
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
            LinkNotFound::class => Response::HTTP_NOT_FOUND,
            InvalidLinkUrl::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidTag::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidIcon::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidPartialUpdateFieldsException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
