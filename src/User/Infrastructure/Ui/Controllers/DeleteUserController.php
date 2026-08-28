<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\Delete\DeleteUserCommand;
use App\User\Domain\Exception\NotFoundUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DeleteUserController extends BaseApiController
{
    /**
     * @throws \Throwable
     */
    public function __invoke(string $id): JsonResponse
    {
        $this->dispatch(
            new DeleteUserCommand(id: $id)
        );

        return new JsonResponse([]);
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            NotFoundUser::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
