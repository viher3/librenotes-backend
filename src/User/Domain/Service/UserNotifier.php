<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

use App\Core\Domain\ValueObject\ContactData\EmailValueObject;

interface UserNotifier
{
    public function sendActivationEmail(EmailValueObject $email, string $activationToken): void;

    public function sendPasswordChangedEmail(EmailValueObject $email, string $locale): void;
}
