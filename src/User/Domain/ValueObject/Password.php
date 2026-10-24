<?php

declare(strict_types=1);

namespace App\User\Domain\ValueObject;

use App\Core\Domain\ValueObject\StringValueObject;
use App\Security\Domain\Service\PasswordHasher;
use App\User\Domain\Exception\InvalidNewPassword;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class Password extends StringValueObject
{
    public const int MIN_LENGTH = 8;
    public const int MAX_LENGTH = 44;

    public function __construct(
        private PasswordHasher $passwordHasher,
        string $value
    )
    {
        $length = mb_strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidNewPassword(self::MIN_LENGTH, self::MAX_LENGTH);
        }

        parent::__construct($value);
    }

    public function hashedValue(PasswordAuthenticatedUserInterface $user): string
    {
        return $this->passwordHasher->hash($user, $this->value);
    }
}
