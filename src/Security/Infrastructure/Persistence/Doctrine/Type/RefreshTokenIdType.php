<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Security\Domain\ValueObject\RefreshTokenId;

/**
 * @extends AbstractStringValueObjectType<RefreshTokenId>
 */
final class RefreshTokenIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'refresh_token_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return RefreshTokenId::class;
    }
}
