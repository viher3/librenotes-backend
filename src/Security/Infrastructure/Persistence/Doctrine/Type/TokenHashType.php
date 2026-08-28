<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractTextValueObjectType;
use App\Security\Domain\ValueObject\TokenHash;

/**
 * @extends AbstractTextValueObjectType<TokenHash>
 */
final class TokenHashType extends AbstractTextValueObjectType
{
    public const string NAME = 'token_hash';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return TokenHash::class;
    }
}
