<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Type;

use App\Configuration\Domain\Model\ValueObject\ConfigurationValue;
use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractTextValueObjectType;

/**
 * @extends AbstractTextValueObjectType<ConfigurationValue>
 */
final class ConfigurationValueType extends AbstractTextValueObjectType
{
    public const string NAME = 'configuration_value';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ConfigurationValue::class;
    }
}
