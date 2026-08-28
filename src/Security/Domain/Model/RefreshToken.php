<?php

declare(strict_types=1);

namespace App\Security\Domain\Model;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Security\Domain\ValueObject\DeviceInfo;
use App\Security\Domain\ValueObject\IpAddress;
use App\Security\Domain\ValueObject\Jti;
use App\Security\Domain\ValueObject\RefreshTokenId;
use App\Security\Domain\ValueObject\TokenHash;
use App\User\Domain\ValueObject\UserId;

class RefreshToken extends AggregateRoot
{
    private BoolValueObject $revoked;

    public function __construct(
        private RefreshTokenId $id,
        private UserId $userId,
        private TokenHash $tokenHash,
        private Jti $jti,
        private DeviceInfo $deviceInfo,
        private DateTimeValueObject $createdAt,
        private DateTimeValueObject $expiresAt,
        private IpAddress $ip,
        private Jti $replacedByJti = new Jti(''),
        bool $revoked = false,
        private ?DateTimeValueObject $lastUsed = null
    ) {
        $this->revoked = new BoolValueObject($revoked);
    }

    public function getId(): RefreshTokenId
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function getTokenHash(): TokenHash
    {
        return $this->tokenHash;
    }

    public function getJti(): Jti
    {
        return $this->jti;
    }

    public function getDeviceInfo(): DeviceInfo
    {
        return $this->deviceInfo;
    }

    public function getCreatedAt(): DateTimeValueObject
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeValueObject
    {
        return $this->expiresAt;
    }

    public function getLastUsed(): ?DateTimeValueObject
    {
        return $this->lastUsed;
    }

    public function isRevoked(): bool
    {
        return $this->revoked->value();
    }

    public function getReplacedByJti(): Jti
    {
        return $this->replacedByJti;
    }

    public function getIp(): IpAddress
    {
        return $this->ip;
    }

    public function updateLastUsed(\DateTimeInterface $lastUsed): void
    {
        $this->lastUsed = new DateTimeValueObject($lastUsed);
    }

    public function revoke(string $replacedByJti = ''): void
    {
        $this->revoked = new BoolValueObject(true);
        $this->replacedByJti = new Jti($replacedByJti);
    }

    public function isExpired(\DateTimeInterface $now): bool
    {
        return $this->expiresAt->value() < $now;
    }
}
