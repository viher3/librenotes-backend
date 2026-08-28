<?php

namespace App\User\Application\Search;

use App\Core\Application\Query\SearchResponse;
use App\Shared\Domain\Time\Date;
use App\User\Domain\Model\User;

class SearchUserResponse implements SearchResponse
{
    public function __construct(
        private array $users,
        private int $totalUsers
    )
    {
    }

    public function getTotal(): int
    {
        return $this->totalUsers;
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function getResult(): array
    {
        $response = [];

        /** @var User $user */
        foreach($this->users as $user){
            $response[] = [
                'id' => $user->getId()->value(),
                'email' => $user->getEmail()->value(),
                'username' => $user->getUsername()->value(),
                'createdAt' => $user->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                'lastLoginAt' => $user->getLastLoginAt()?->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            ];
        }

        return $response;
    }
}
