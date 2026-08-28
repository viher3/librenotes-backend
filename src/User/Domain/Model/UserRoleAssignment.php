<?php

declare(strict_types=1);

namespace App\User\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\User\Domain\ValueObject\UserRoleAssignmentId;
use App\User\Domain\ValueObject\UserRoleId;
use App\User\Domain\ValueObject\UserId;

/**
 * Extends AggregateRoot only because DoctrineRepository::persist()/remove() require it,
 * like NotificationTemplate. It records no domain events.
 */
class UserRoleAssignment extends AggregateRoot
{
    public function __construct(
        private UserRoleAssignmentId $id,
        private UserId $userId,
        private UserRoleId $userRoleId,
        private DateTimeValueObject $assignedAt,
        private ?DateTimeValueObject $revokedAt = null
    ) {
    }

    public function revoke(DateTimeValueObject $revokedAt): void
    {
        $this->revokedAt = $revokedAt;
    }

    public function getId(): UserRoleAssignmentId
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getUserRoleId(): UserRoleId
    {
        return $this->userRoleId;
    }

    public function getAssignedAt(): DateTimeValueObject
    {
        return $this->assignedAt;
    }

    public function getRevokedAt(): ?DateTimeValueObject
    {
        return $this->revokedAt;
    }
}
