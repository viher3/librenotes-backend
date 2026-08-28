<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\User\Domain\ValueObject\UserRoleId;
use App\User\Domain\ValueObject\UserRoleName;

/**
 * Extends AggregateRoot only because DoctrineRepository::persist()/remove() require it,
 * like NotificationTemplate. It records no domain events.
 */
class UserRole extends AggregateRoot
{
    public function __construct(
        private UserRoleId $id,
        private UserRoleName $name,
        private DateTimeValueObject $createdAt,
        private ?DateTimeValueObject $deletedAt = null,
    )
    {
    }

    public function getId(): UserRoleId
    {
        return $this->id;
    }

    public function getName(): UserRoleName
    {
        return $this->name;
    }

    public function getCreatedAt(): DateTimeValueObject
    {
        return $this->createdAt;
    }

    public function getDeletedAt(): ?DateTimeValueObject
    {
        return $this->deletedAt;
    }
}
