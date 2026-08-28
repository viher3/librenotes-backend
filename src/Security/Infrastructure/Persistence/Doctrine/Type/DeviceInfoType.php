<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractJsonValueObjectType;
use App\Security\Domain\ValueObject\DeviceInfo;

/**
 * @extends AbstractJsonValueObjectType<DeviceInfo>
 */
final class DeviceInfoType extends AbstractJsonValueObjectType
{
    public const string NAME = 'device_info';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return DeviceInfo::class;
    }
}
