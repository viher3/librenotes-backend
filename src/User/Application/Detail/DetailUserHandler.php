<?php

declare(strict_types=1);

namespace App\User\Application\Detail;

use App\Core\Application\Query\QueryHandler;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\UserRepository;

class DetailUserHandler implements QueryHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(DetailUserQuery $query): DetailUserResponse
    {
        $user = $this->userRepository->getById($query->id);

        if(!$user){
            throw new NotFoundUser($query->id);
        }

        return new DetailUserResponse($user);
    }
}
