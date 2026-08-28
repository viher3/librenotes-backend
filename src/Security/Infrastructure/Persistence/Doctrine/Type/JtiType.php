<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Security\Domain\ValueObject\Jti;

/**
 * @extends AbstractStringValueObjectType<Jti>
 */
final class JtiType extends AbstractStringValueObjectType
{
    public const string NAME = 'jti';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return Jti::class;
    }
}
