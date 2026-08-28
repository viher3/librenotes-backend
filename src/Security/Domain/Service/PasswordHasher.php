<?php

declare(strict_types=1);

namespace App\Security\Domain\Service;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

interface PasswordHasher
{
    public function hash(PasswordAuthenticatedUserInterface $user, string $password): string;

    public function verify(PasswordAuthenticatedUserInterface $user, string $password): bool;
}
