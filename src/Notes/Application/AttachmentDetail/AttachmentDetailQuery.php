<?php

declare(strict_types=1);

namespace App\Notes\Application\AttachmentDetail;

use App\Core\Application\Query\Query;

readonly class AttachmentDetailQuery implements Query
{
    public function __construct(
        public string $id,
        public string $ownerId
    ) {
    }
}
