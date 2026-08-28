<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\User\Domain\ValueObject\PasswordRecoveryToken;

/**
 * @extends AbstractStringValueObjectType<PasswordRecoveryToken>
 */
final class PasswordRecoveryTokenType extends AbstractStringValueObjectType
{
    public const string NAME = 'password_recovery_token';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return PasswordRecoveryToken::class;
    }
}
