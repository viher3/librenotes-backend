<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Domain\ValueObject\IntValueObject;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;

/**
 * @template T of IntValueObject
 */
abstract class AbstractIntValueObjectType extends IntegerType
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

        return new $class((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof IntValueObject) {
            $value = $value->value();
        }

        return (int) $value;
    }
}
