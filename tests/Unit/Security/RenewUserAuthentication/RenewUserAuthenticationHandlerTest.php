<?php

namespace App\Tests\Unit\Security\RenewUserAuthentication;

use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Security\Application\RenewUserAuthentication\RenewUserAuthenticationCommand;
use App\Security\Application\RenewUserAuthentication\RenewUserAuthenticationHandler;
use App\Security\Application\RenewUserAuthentication\RenewUserAuthenticationResponse;
use App\Security\Domain\Exception\ExpiredTokenException;
use App\Security\Domain\Exception\InvalidTokenException;
use App\Security\Domain\Exception\TooManyAttemptsException;
use App\Security\Domain\Model\RefreshToken;
use App\Security\Domain\Repository\RefreshTokenRepository;
use App\Security\Domain\Service\GeneratedRefreshToken;
use App\Security\Domain\Service\LoginRateLimiter;
use App\Security\Domain\Service\RefreshTokenGenerator;
use App\Security\Domain\Service\TokenEncoderDecoder;
use App\Security\Domain\ValueObject\DeviceInfo;
use App\Security\Domain\ValueObject\IpAddress;
use App\Security\Domain\ValueObject\Jti;
use App\Security\Domain\ValueObject\RefreshTokenId;
use App\Security\Domain\ValueObject\TokenHash;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class RenewUserAuthenticationHandlerTest extends TestCase
{
    private const string REFRESH_TOKEN_ID = '22222222-2222-2222-2222-222222222222';
    private const string USER_ID = '11111111-1111-1111-1111-111111111111';

    private RefreshTokenRepository $refreshTokenRepository;
    private RefreshTokenGenerator $refreshTokenGenerator;
    private TokenEncoderDecoder $tokenEncoderDecoder;
    private LoginRateLimiter $loginRateLimiter;
    private DateTimeProvider $dateTimeProvider;
    private RenewUserAuthenticationHandler $handler;

    protected function setUp(): void
    {
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->refreshTokenGenerator = $this->createMock(RefreshTokenGenerator::class);
        $this->tokenEncoderDecoder = $this->createMock(TokenEncoderDecoder::class);
        $this->loginRateLimiter = $this->createMock(LoginRateLimiter::class);
        $this->dateTimeProvider = $this->createMock(DateTimeProvider::class);

        $this->handler = new RenewUserAuthenticationHandler(
            $this->refreshTokenRepository,
            $this->refreshTokenGenerator,
            $this->tokenEncoderDecoder,
            $this->loginRateLimiter,
            $this->dateTimeProvider
        );
    }

    private function buildRefreshToken(
        string $userId = self::USER_ID,
        bool $revoked = false,
        ?\DateTimeInterface $expiresAt = null
    ): RefreshToken {
        return new RefreshToken(
            id: new RefreshTokenId(self::REFRESH_TOKEN_ID),
            userId: new UserId($userId),
            tokenHash: new TokenHash(hash('sha256', 'plain-refresh-token')),
            jti: new Jti('old-jti'),
            deviceInfo: new DeviceInfo(['user_agent' => 'phpunit', 'platform' => 'web']),
            createdAt: new DateTimeValueObject(new \DateTimeImmutable('-1 day')),
            expiresAt: new DateTimeValueObject($expiresAt ?? new \DateTimeImmutable('+29 days')),
            ip: new IpAddress('127.0.0.1'),
            revoked: $revoked
        );
    }

    public function testThrowsInvalidTokenExceptionWhenTokenNotFound(): void
    {
        $this->refreshTokenRepository->method('findByTokenHash')->willReturn(null);

        $this->expectException(InvalidTokenException::class);

        ($this->handler)(new RenewUserAuthenticationCommand('unknown-token', '127.0.0.1'));
    }

    public function testThrowsExpiredTokenExceptionWhenTokenExpired(): void
    {
        $refreshToken = $this->buildRefreshToken(expiresAt: new \DateTimeImmutable('-1 minute'));
        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($refreshToken);
        $this->dateTimeProvider->method('now')->willReturn(new \DateTimeImmutable());

        $this->expectException(ExpiredTokenException::class);

        ($this->handler)(new RenewUserAuthenticationCommand('plain-refresh-token', '127.0.0.1'));
    }

    public function testRevokesActiveFamilyWhenAlreadyRevokedTokenIsReused(): void
    {
        $reusedToken = $this->buildRefreshToken(revoked: true);
        $activeTokenOne = $this->buildRefreshToken();
        $activeTokenTwo = $this->buildRefreshToken();

        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($reusedToken);
        $this->refreshTokenRepository->method('findActiveByUserId')
            ->with(self::USER_ID)
            ->willReturn([$activeTokenOne, $activeTokenTwo]);

        $this->refreshTokenRepository->expects($this->exactly(2))->method('save');

        $this->expectException(InvalidTokenException::class);

        try {
            ($this->handler)(new RenewUserAuthenticationCommand('stolen-and-rotated-token', '127.0.0.1'));
        } finally {
            $this->assertTrue($activeTokenOne->isRevoked());
            $this->assertTrue($activeTokenTwo->isRevoked());
        }
    }

    public function testThrowsExceptionWhenTooManyRenewAttempts(): void
    {
        $this->loginRateLimiter->method('consumeRenewAttempt')
            ->willThrowException(new TooManyAttemptsException());

        $this->refreshTokenRepository->expects($this->never())->method('findByTokenHash');

        $this->expectException(TooManyAttemptsException::class);

        ($this->handler)(new RenewUserAuthenticationCommand('plain-refresh-token', '127.0.0.1'));
    }

    public function testRotatesRefreshTokenOnSuccessfulRenewal(): void
    {
        $refreshToken = $this->buildRefreshToken();
        $now = new \DateTimeImmutable();

        $this->refreshTokenRepository->method('findByTokenHash')->willReturn($refreshToken);
        $this->dateTimeProvider->method('now')->willReturn($now);

        $newRefreshToken = new GeneratedRefreshToken('new-plain-refresh-token', 'new-jti');
        $this->refreshTokenGenerator->method('generate')
            ->with(new UserId(self::USER_ID))
            ->willReturn($newRefreshToken);

        $this->tokenEncoderDecoder->method('encode')
            ->with(['id' => self::USER_ID])
            ->willReturn('new-access-token');

        $this->refreshTokenRepository->expects($this->once())
            ->method('save')
            ->with($refreshToken);

        $response = ($this->handler)(new RenewUserAuthenticationCommand('plain-refresh-token', '127.0.0.1'));

        $this->assertInstanceOf(RenewUserAuthenticationResponse::class, $response);
        $this->assertSame('new-access-token', $response->getAccessToken());
        $this->assertSame('new-plain-refresh-token', $response->getRefreshToken());
        $this->assertTrue($refreshToken->isRevoked());
        $this->assertSame('new-jti', $refreshToken->getReplacedByJti()->value());
        $this->assertEquals($now, $refreshToken->getLastUsed()->value());
    }
}
