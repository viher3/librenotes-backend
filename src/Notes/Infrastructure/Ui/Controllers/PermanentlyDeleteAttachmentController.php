<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\PermanentlyDeleteAttachment\PermanentlyDeleteAttachmentCommand;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PermanentlyDeleteAttachmentController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $id): JsonResponse
    {
        $this->dispatchTransactional(
            new PermanentlyDeleteAttachmentCommand(
                id: $id,
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID
            )
        );

        return new JsonResponse([]);
    }

    protected function exceptions(): array
    {
        return [
            AttachmentNotFound::class => Response::HTTP_NOT_FOUND,
            NotInTrash::class => Response::HTTP_CONFLICT,
        ];
    }
}
