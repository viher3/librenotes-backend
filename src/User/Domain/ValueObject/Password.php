<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;
use App\Security\Domain\Service\PasswordHasher;
use App\User\Domain\Exception\InvalidUserPassword;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class Password extends StringValueObject
{
    public function __construct(
        private PasswordHasher $passwordHasher,
        string $value
    )
    {
        if (strlen($value) < 5 || strlen($value) > 20) {
            throw new InvalidUserPassword(
                'La contraseña debe tener entre 5 y 20 caracteres'
            );
        }

        parent::__construct($value);
    }

    public function hashedValue(PasswordAuthenticatedUserInterface $user): string
    {
        return $this->passwordHasher->hash($user, $this->value);
    }
}
