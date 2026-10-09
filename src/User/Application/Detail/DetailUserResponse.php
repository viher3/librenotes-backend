<?php

namespace App\User\Application\Detail;

use App\Core\Application\Query\QueryResponse;
use App\Shared\Domain\Time\Date;
use App\User\Domain\Model\User;

class DetailUserResponse implements QueryResponse
{
    public function __construct(
        public User $user
    )
    {
    }

    public function getResult(): array
    {
        return [
            'id' => $this->user->getId()->value(),
            'username' => $this->user->getUsername()->value(),
            'email' => $this->user->getEmail()->value(),
            'createdAt' => $this->user->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            'active' => $this->user->isActive(),
            'emailValidated' => $this->user->isEmailValidated(),
            'locale' => $this->user->getLocale()->value(),
            //'lastLoginAt' => $this->user->getLastLoginAt()?->format(Date::DATE_DEFAULT_RENDER_FORMAT),
        ];
    }
}
