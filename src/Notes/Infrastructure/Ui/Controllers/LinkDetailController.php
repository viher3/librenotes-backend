<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\LinkDetail\LinkDetailQuery;
use App\Notes\Application\LinkDetail\LinkDetailResponse;
use App\Notes\Domain\Exception\LinkNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LinkDetailController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        /** @var LinkDetailResponse $response */
        $response = $this->ask(
            new LinkDetailQuery(
                id: $id,
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse($response->getResult());
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            LinkNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
