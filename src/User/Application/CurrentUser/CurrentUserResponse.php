<?php

declare(strict_types=1);

namespace App\User\Application\CurrentUser;

use App\Core\Application\Query\QueryResponse;
use App\Shared\Domain\Time\Date;
use App\User\Domain\Model\User;

class CurrentUserResponse implements QueryResponse
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        private User $user,
        private array $roles
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'id' => $this->user->getId()->value(),
            'email' => $this->user->getEmail()->value(),
            'username' => $this->user->getUsername()->value(),
            'locale' => $this->user->getLocale()->value(),
            'roles' => array_values($this->roles),
            'active' => $this->user->isActive(),
            'email_validated' => $this->user->isEmailValidated(),
            'created_at' => $this->user->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            'last_login_at' => $this->user->getLastLoginAt()?->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
        ];
    }
}
