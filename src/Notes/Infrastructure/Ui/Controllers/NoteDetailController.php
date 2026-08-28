<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\NoteDetail\NoteDetailQuery;
use App\Notes\Application\NoteDetail\NoteDetailResponse;
use App\Notes\Domain\Exception\NoteNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NoteDetailController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $id): JsonResponse
    {
        /** @var NoteDetailResponse $response */
        $response = $this->ask(
            new NoteDetailQuery(
                id: $id,
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID
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
            NoteNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
