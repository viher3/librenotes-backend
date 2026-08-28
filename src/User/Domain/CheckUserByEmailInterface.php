<?php

namespace App\User\Domain;

interface CheckUserByEmailInterface
{
    public function isEmailAvailable(string $email): bool;
}