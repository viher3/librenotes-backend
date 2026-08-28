<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractIntValueObjectType;
use App\User\Domain\ValueObject\TokenVersion;

/**
 * @extends AbstractIntValueObjectType<TokenVersion>
 */
final class TokenVersionType extends AbstractIntValueObjectType
{
    public const string NAME = 'token_version';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return TokenVersion::class;
    }
}
