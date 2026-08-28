<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\AttachmentDetail\AttachmentDetailQuery;
use App\Notes\Application\AttachmentDetail\AttachmentDetailResponse;
use App\Notes\Domain\Exception\AttachmentNotFound;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AttachmentDetailController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $id): JsonResponse
    {
        /** @var AttachmentDetailResponse $response */
        $response = $this->ask(
            new AttachmentDetailQuery(
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
            AttachmentNotFound::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
