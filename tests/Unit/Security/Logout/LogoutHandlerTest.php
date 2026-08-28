<?php

namespace App\Tests\Unit\Security\Logout;

use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Security\Application\Logout\LogoutCommand;
use App\Security\Application\Logout\LogoutHandler;
use App\Security\Domain\Model\RefreshToken;
use App\Security\Domain\Repository\RefreshTokenRepository;
use App\Security\Domain\ValueObject\DeviceInfo;
use App\Security\Domain\ValueObject\IpAddress;
use App\Security\Domain\ValueObject\Jti;
use App\Security\Domain\ValueObject\RefreshTokenId;
use App\Security\Domain\ValueObject\TokenHash;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class LogoutHandlerTest extends TestCase
{
    private const string REFRESH_TOKEN_ID = '22222222-2222-2222-2222-222222222222';
    private const string USER_ID = '11111111-1111-1111-1111-111111111111';

    private RefreshTokenRepository $refreshTokenRepository;
    private LogoutHandler $handler;

    protected function setUp(): void
    {
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->handler = new LogoutHandler($this->refreshTokenRepository);
    }

    private function buildRefreshToken(bool $revoked = false): RefreshToken
    {
        return new RefreshToken(
            id: new RefreshTokenId(self::REFRESH_TOKEN_ID),
            userId: new UserId(self::USER_ID),
            tokenHash: new TokenHash(hash('sha256', 'plain-refresh-token')),
            jti: new Jti('jti-123'),
            deviceInfo: new DeviceInfo(['user_agent' => 'phpunit', 'platform' => 'web']),
            createdAt: new DateTimeValueObject(new \DateTimeImmutable('-1 day')),
            expiresAt: new DateTimeValueObject(new \DateTimeImmutable('+29 days')),
            ip: new IpAddress('127.0.0.1'),
            revoked: $revoked
        );
    }

    public function testRevokesAnActiveToken(): void
    {
        $refreshToken = $this->buildRefreshToken();

        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($refreshToken);
        $this->refreshTokenRepository->expects($this->once())
            ->method('save')
            ->with($refreshToken);

        ($this->handler)(new LogoutCommand('plain-refresh-token'));

        $this->assertTrue($refreshToken->isRevoked());
    }

    public function testIsANoOpWhenTokenIsUnknown(): void
    {
        $this->refreshTokenRepository->method('findByTokenHash')->willReturn(null);
        $this->refreshTokenRepository->expects($this->never())->method('save');

        ($this->handler)(new LogoutCommand('unknown-token'));
    }

    public function testIsANoOpWhenTokenIsAlreadyRevoked(): void
    {
        $refreshToken = $this->buildRefreshToken(revoked: true);

        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($refreshToken);
        $this->refreshTokenRepository->expects($this->never())->method('save');

        ($this->handler)(new LogoutCommand('plain-refresh-token'));
    }
}
