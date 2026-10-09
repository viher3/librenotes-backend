<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\User\Domain\ValueObject\UserLocale;

/**
 * @extends AbstractStringValueObjectType<UserLocale>
 */
final class UserLocaleType extends AbstractStringValueObjectType
{
    public const string NAME = 'user_locale';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return UserLocale::class;
    }
}
