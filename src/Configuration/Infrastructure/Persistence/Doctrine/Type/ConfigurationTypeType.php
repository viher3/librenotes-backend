<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Type;

use App\Configuration\Domain\Model\ValueObject\ConfigurationType;
use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;

/**
 * @extends AbstractStringValueObjectType<ConfigurationType>
 */
final class ConfigurationTypeType extends AbstractStringValueObjectType
{
    public const string NAME = 'configuration_type';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ConfigurationType::class;
    }
}
