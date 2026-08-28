<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Persistence\Doctrine\Type;

use App\Core\Infrastructure\Persistence\Doctrine\Type\AbstractStringValueObjectType;
use App\Security\Domain\ValueObject\IpAddress;

/**
 * @extends AbstractStringValueObjectType<IpAddress>
 */
final class IpAddressType extends AbstractStringValueObjectType
{
    public const string NAME = 'ip_address';

    public function getName(): string
    {
        return self::NAME;
    }

    protected function valueObjectClass(): string
    {
        return IpAddress::class;
    }
}
