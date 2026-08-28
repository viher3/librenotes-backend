<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Persistence\Doctrine\Type;

use App\Configuration\Domain\Model\ValueObject\ConfigurationHistoryAction;
use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;

/**
 * @extends AbstractStringValueObjectType<ConfigurationHistoryAction>
 */
final class ConfigurationHistoryActionType extends AbstractStringValueObjectType
{
    public const string NAME = 'configuration_history_action';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return ConfigurationHistoryAction::class;
    }
}
