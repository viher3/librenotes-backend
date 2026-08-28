<?php

namespace App\User\Infrastructure\Specification;

use App\Core\Domain\Specification\AbstractSpecification;
use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\User\Domain\CheckUserByEmailInterface;
use App\User\Domain\Exception\UserEmailAlreadyExists;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;

class UniqueEmailSpecification extends AbstractSpecification implements UniqueEmailSpecificationInterface
{
    public function __construct(
        private CheckUserByEmailInterface $checkUserByEmailInterface
    )
    {
    }

    public function isSatisfiedBy(mixed $value): bool
    {
        if($this->checkUserByEmailInterface->isEmailAvailable($value)){
            throw new UserEmailAlreadyExists($value);
        }

        return true;
    }

    public function isUnique(EmailValueObject $email): bool
    {
        return $this->isSatisfiedBy($email->value());
    }
}