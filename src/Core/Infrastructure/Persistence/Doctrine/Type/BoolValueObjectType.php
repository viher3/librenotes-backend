<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Domain\ValueObject\BoolValueObject;

/**
 * @extends AbstractBoolValueObjectType<BoolValueObject>
 */
final class BoolValueObjectType extends AbstractBoolValueObjectType
{
    public const string NAME = 'bool_vo';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return BoolValueObject::class;
    }
}
