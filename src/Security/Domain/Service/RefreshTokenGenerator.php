<?php

namespace App\Security\Domain\Service;

use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Security\Domain\Model\RefreshToken;
use App\Security\Domain\Repository\RefreshTokenRepository;
use App\Security\Domain\ValueObject\DeviceInfo;
use App\Security\Domain\ValueObject\IpAddress;
use App\Security\Domain\ValueObject\Jti;
use App\Security\Domain\ValueObject\RefreshTokenId;
use App\Security\Domain\ValueObject\TokenHash;
use App\User\Domain\ValueObject\UserId;
use Random\RandomException;

class RefreshTokenGenerator
{
    public function __construct(
        private DateTimeProvider $dateTimeProvider,
        private RefreshTokenRepository $refreshTokenRepository
    )
    {
    }

    /**
     * @throws RandomException
     */
    public function generate(UserId $userId): GeneratedRefreshToken
    {
        $refreshTokenValue = bin2hex(random_bytes(32));
        $jti = bin2hex(random_bytes(16));
        $now = $this->dateTimeProvider->now();

        $refreshToken = new RefreshToken(
            id: RefreshTokenId::random(),
            userId: $userId,
            tokenHash: new TokenHash(hash('sha256', $refreshTokenValue)),
            jti: new Jti($jti),
            deviceInfo: new DeviceInfo([
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'platform' => 'web'
            ]),
            createdAt: new DateTimeValueObject($now),
            expiresAt: new DateTimeValueObject(\DateTimeImmutable::createFromInterface($now)->modify('+30 days')),
            ip: new IpAddress($_SERVER['REMOTE_ADDR'] ?? 'unknown')
        );

        $this->refreshTokenRepository->save($refreshToken);

        return new GeneratedRefreshToken($refreshTokenValue, $jti);
    }
}
