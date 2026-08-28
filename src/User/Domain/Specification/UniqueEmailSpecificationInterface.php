<?php

namespace App\User\Domain\Specification;

use App\Core\Domain\ValueObject\ContactData\EmailValueObject;

interface UniqueEmailSpecificationInterface
{
    public function isUnique(EmailValueObject $email): bool;
}