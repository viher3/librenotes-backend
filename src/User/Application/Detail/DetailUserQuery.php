<?php

declare(strict_types=1);

namespace App\User\Application\Detail;

use App\Core\Application\Query\Query;

readonly class DetailUserQuery implements Query
{
    public function __construct(
        public string $id
    )
    {
    }
}
