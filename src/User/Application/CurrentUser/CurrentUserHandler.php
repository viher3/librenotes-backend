<?php

declare(strict_types=1);

namespace App\User\Application\CurrentUser;

use App\Core\Application\Query\QueryHandler;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\UserRepository;

class CurrentUserHandler implements QueryHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(CurrentUserQuery $query): CurrentUserResponse
    {
        $user = $this->userRepository->getById($query->id);

        if (!$user) {
            throw new NotFoundUser($query->id);
        }

        return new CurrentUserResponse($user, $query->roles);
    }
}
