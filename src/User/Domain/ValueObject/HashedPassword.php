<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Core\Domain\ValueObject\String255MaxLengthValueObject;

class HashedPassword extends String255MaxLengthValueObject
{
}
