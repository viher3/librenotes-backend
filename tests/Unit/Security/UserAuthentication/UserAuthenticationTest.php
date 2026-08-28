<?php

namespace App\Tests\Unit\Security\UserAuthentication;

use App\Core\Domain\Time\DateTimeProvider;
use App\Security\Application\UserAuthentication\UserAuthenticationCommand;
use App\Security\Application\UserAuthentication\UserAuthenticationHandler;
use App\Security\Application\UserAuthentication\UserAuthenticationResponse;
use App\Security\Domain\Exception\BadCredentialsException;
use App\Security\Domain\Exception\InactiveUserException;
use App\Security\Domain\Exception\TooManyAttemptsException;
use App\Security\Domain\Service\GeneratedRefreshToken;
use App\Security\Domain\Service\LoginRateLimiter;
use App\Security\Domain\Service\PasswordHasher;
use App\Security\Domain\Service\RefreshTokenGenerator;
use App\Security\Domain\Service\TokenEncoderDecoder;
use App\User\Domain\Model\User;
use App\User\Domain\UserRepository;
use App\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserAuthenticationTest extends TestCase
{
    private const string USER_ID = '11111111-1111-1111-1111-111111111111';

    private UserRepository $userRepository;
    private PasswordHasher $passwordHasher;
    private TokenEncoderDecoder $tokenEncoderDecoder;
    private RefreshTokenGenerator $refreshTokenGenerator;
    private LoginRateLimiter $loginRateLimiter;
    private LoggerInterface $logger;
    private DateTimeProvider $dateTimeProvider;
    private UserAuthenticationHandler $handler;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(PasswordHasher::class);
        $this->tokenEncoderDecoder = $this->createMock(TokenEncoderDecoder::class);
        $this->refreshTokenGenerator = $this->createMock(RefreshTokenGenerator::class);
        $this->loginRateLimiter = $this->createMock(LoginRateLimiter::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dateTimeProvider = $this->createMock(DateTimeProvider::class);

        $this->handler = new UserAuthenticationHandler(
            $this->userRepository,
            $this->passwordHasher,
            $this->tokenEncoderDecoder,
            $this->refreshTokenGenerator,
            $this->loginRateLimiter,
            $this->logger,
            $this->dateTimeProvider
        );
    }

    public function testAuthenticatesUserSuccessfully(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(new UserId(self::USER_ID));
        $user->method('isActive')->willReturn(true);

        $command = new UserAuthenticationCommand('test@example.com', 'password123', '127.0.0.1');

        $this->userRepository->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->passwordHasher->method('verify')
            ->with($user, 'password123')
            ->willReturn(true);

        $now = new \DateTimeImmutable();
        $this->dateTimeProvider->method('now')->willReturn($now);

        $user->expects($this->once())
            ->method('loggedAt')
            ->with($now);

        $this->userRepository->expects($this->once())
            ->method('save')
            ->with($user);

        $this->tokenEncoderDecoder->method('encode')
            ->with(['id' => self::USER_ID])
            ->willReturn('generated-token');

        $this->refreshTokenGenerator->method('generate')
            ->with(new UserId(self::USER_ID))
            ->willReturn(new GeneratedRefreshToken('generated-refresh-token', 'jti-123'));

        $response = ($this->handler)($command);

        $this->assertInstanceOf(UserAuthenticationResponse::class, $response);
    }

    public function testThrowsExceptionWhenUserNotFound(): void
    {
        $command = new UserAuthenticationCommand('nonexistent@example.com', 'password123', '127.0.0.1');

        $this->userRepository->method('findByEmail')
            ->with('nonexistent@example.com')
            ->willReturn(null);

        $this->expectException(BadCredentialsException::class);

        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenPasswordIsInvalid(): void
    {
        $user = $this->createMock(User::class);
        $command = new UserAuthenticationCommand('test@example.com', 'wrong-password', '127.0.0.1');

        $this->userRepository->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->passwordHasher->method('verify')
            ->with($user, 'wrong-password')
            ->willReturn(false);

        $this->expectException(BadCredentialsException::class);

        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenUserIsInactive(): void
    {
        $user = $this->createMock(User::class);
        $command = new UserAuthenticationCommand('test@example.com', 'password123', '127.0.0.1');

        $this->userRepository->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->passwordHasher->method('verify')
            ->with($user, 'password123')
            ->willReturn(true);

        $user->method('isActive')->willReturn(false);

        $this->expectException(InactiveUserException::class);

        ($this->handler)($command);
    }

    public function testThrowsExceptionWhenTooManyLoginAttempts(): void
    {
        $command = new UserAuthenticationCommand('test@example.com', 'password123', '127.0.0.1');

        $this->loginRateLimiter->method('consumeLoginAttempt')
            ->willThrowException(new TooManyAttemptsException());

        $this->userRepository->expects($this->never())->method('findByEmail');

        $this->expectException(TooManyAttemptsException::class);

        ($this->handler)($command);
    }

    public function testLogsErrorWhenSaveFails(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(new UserId(self::USER_ID));
        $user->method('isActive')->willReturn(true);

        $command = new UserAuthenticationCommand('test@example.com', 'password123', '127.0.0.1');

        $this->userRepository->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->passwordHasher->method('verify')
            ->with($user, 'password123')
            ->willReturn(true);

        $now = new \DateTimeImmutable();
        $this->dateTimeProvider->method('now')->willReturn($now);

        $exception = new \Exception('Database error');
        $this->userRepository->method('save')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error updating last user login value: Database error');

        $this->tokenEncoderDecoder->method('encode')
            ->with(['id' => self::USER_ID])
            ->willReturn('generated-token');

        $this->refreshTokenGenerator->method('generate')
            ->with(new UserId(self::USER_ID))
            ->willReturn(new GeneratedRefreshToken('generated-refresh-token', 'jti-123'));

        $response = ($this->handler)($command);

        $this->assertInstanceOf(UserAuthenticationResponse::class, $response);
    }
}