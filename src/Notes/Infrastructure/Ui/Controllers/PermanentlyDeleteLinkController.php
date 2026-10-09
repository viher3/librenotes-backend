<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\PermanentlyDeleteLink\PermanentlyDeleteLinkCommand;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PermanentlyDeleteLinkController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        $this->dispatchTransactional(
            new PermanentlyDeleteLinkCommand(
                id: $id,
                ownerId: $this->currentUserId()
            )
        );

        return new JsonResponse([]);
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            LinkNotFound::class => Response::HTTP_NOT_FOUND,
            NotInTrash::class => Response::HTTP_CONFLICT,
        ];
    }
}
