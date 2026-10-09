<?php

declare(strict_types=1);

namespace App\User\Application\CurrentUser;

use App\Core\Application\Query\Query;

readonly class CurrentUserQuery implements Query
{
    /**
     * @param string[] $roles
     */
    public function __construct(
        public string $id,
        public array $roles
    ) {
    }
}
