<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\CurrentUser\CurrentUserQuery;
use App\User\Application\CurrentUser\CurrentUserResponse;
use App\User\Domain\Exception\NotFoundUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CurrentUserController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        /** @var CurrentUserResponse $response */
        $response = $this->ask(
            new CurrentUserQuery(
                id: $this->currentUserId(),
                roles: $this->getUser()?->getRoles() ?? []
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
            NotFoundUser::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
