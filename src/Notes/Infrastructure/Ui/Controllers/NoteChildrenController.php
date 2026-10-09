<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\NoteChildren\NoteChildrenQuery;
use App\Notes\Application\NoteChildren\NoteChildrenResponse;
use App\Notes\Domain\Exception\NoteNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NoteChildrenController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        /** @var NoteChildrenResponse $response */
        $response = $this->ask(
            new NoteChildrenQuery(
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
            NoteNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
