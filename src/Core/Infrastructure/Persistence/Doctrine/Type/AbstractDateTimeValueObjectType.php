<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Domain\ValueObject\DateValueObject;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * Converts to/from the platform's DATETIME string directly instead of delegating
 * to the parent DateTimeType, which triggers a deprecation warning when handed a
 * \DateTimeImmutable (DateValueObject normalizes to immutable internally).
 *
 * @template T of DateValueObject
 */
abstract class AbstractDateTimeValueObjectType extends DateTimeType
{
    /**
     * @return class-string<T>
     */
    abstract protected function valueObjectClass(): string;

    /**
     * @return T|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?object
    {
        if ($value === null) {
            return null;
        }

        $class = $this->valueObjectClass();

        if ($value instanceof \DateTimeInterface) {
            return new $class(\DateTimeImmutable::createFromInterface($value));
        }

        $dateTime = \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value);

        if ($dateTime === false) {
            $dateTime = new \DateTime($value);
        }

        return new $class(\DateTimeImmutable::createFromInterface($dateTime));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateValueObject) {
            $value = $value->value();
        }

        return $value->format($platform->getDateTimeFormatString());
    }
}
