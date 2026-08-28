<?php

declare(strict_types=1);

namespace App\User\Domain;

use App\Core\Domain\Repository\SearchRepository;
use App\User\Domain\Model\User;

interface UserRepository extends SearchRepository
{
    public function save(User $user): void;

    public function getById(string $id): ?User;

    public function findByUsername(string $username) : ?User;

    public function findByEmail(string $email) : ?User;

    public function findByActivationToken(string $token): ?User;

    public function delete(User $user) : void;
}
