<?php

declare(strict_types=1);

namespace App\Notes\Application\AttachmentContent;

use App\Core\Application\Query\Query;

readonly class AttachmentContentQuery implements Query
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
