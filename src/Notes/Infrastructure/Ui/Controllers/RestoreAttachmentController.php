<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\RestoreAttachment\RestoreAttachmentCommand;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Exception\ParentFolderInTrash;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class RestoreAttachmentController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $id): JsonResponse
    {
        $this->dispatchTransactional(
            new RestoreAttachmentCommand(
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
            ParentFolderInTrash::class => Response::HTTP_CONFLICT,
        ];
    }
}
