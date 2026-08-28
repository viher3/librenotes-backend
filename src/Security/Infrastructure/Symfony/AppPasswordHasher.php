<?php

namespace App\Security\Infrastructure\Symfony;

use App\Security\Domain\Service\PasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class AppPasswordHasher implements PasswordHasher
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function hash(PasswordAuthenticatedUserInterface $user, string $password): string
    {
        return $this->passwordHasher->hashPassword($user, $password);
    }

    public function verify(PasswordAuthenticatedUserInterface $user, string $password): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $password);
    }
}