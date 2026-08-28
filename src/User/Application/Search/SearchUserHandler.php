<?php

namespace App\User\Application\Search;

use App\Core\Application\Query\QueryHandler;
use App\User\Domain\UserRepository;

class SearchUserHandler implements QueryHandler
{
    public function __construct(
        private UserRepository $userRepository
    )
    {
    }

    public function __invoke(SearchUserQuery $query) : SearchUserResponse
    {
        $users = $this->userRepository->search(
            ...$query->getAll()
        );

        $totalUsers = $this->userRepository->searchCount(
            $query->getFilters()
        );

        return new SearchUserResponse($users, $totalUsers);
    }
}
