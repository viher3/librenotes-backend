<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\TextType;

/**
 * @template T of object
 */
abstract class AbstractTextValueObjectType extends TextType
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
        $value = parent::convertToPHPValue($value, $platform);

        if ($value === null) {
            return null;
        }

        $class = $this->valueObjectClass();

        return new $class((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_object($value)) {
            $value = (string) $value;
        }

        return $value;
    }
}
