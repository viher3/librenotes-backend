<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Type;

use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryId;
use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;

/**
 * @extends AbstractStringValueObjectType<ConfigurationHistoryId>
 */
final class ConfigurationHistoryIdType extends AbstractStringValueObjectType
{
    public const string NAME = 'configuration_history_id';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ConfigurationHistoryId::class;
    }
}
