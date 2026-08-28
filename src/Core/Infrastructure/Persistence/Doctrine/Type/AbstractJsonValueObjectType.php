<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Domain\ValueObject\ArrayValueObject;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

/**
 * @template T of ArrayValueObject
 */
abstract class AbstractJsonValueObjectType extends JsonType
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
        $decoded = parent::convertToPHPValue($value, $platform);

        if ($decoded === null) {
            return null;
        }

        $class = $this->valueObjectClass();

        return new $class($decoded);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof ArrayValueObject) {
            $value = $value->value();
        }

        return parent::convertToDatabaseValue($value, $platform);
    }
}
