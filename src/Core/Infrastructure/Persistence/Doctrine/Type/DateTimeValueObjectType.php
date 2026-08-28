<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Domain\ValueObject\DateTimeValueObject;

/**
 * @extends AbstractDateTimeValueObjectType<DateTimeValueObject>
 */
final class DateTimeValueObjectType extends AbstractDateTimeValueObjectType
{
    public const string NAME = 'datetime_vo';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return DateTimeValueObject::class;
    }
}
