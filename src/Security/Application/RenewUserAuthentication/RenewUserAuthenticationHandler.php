<?php

declare(strict_types=1);

namespace App\Security\Application\RenewUserAuthentication;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Security\Domain\Exception\ExpiredTokenException;
use App\Security\Domain\Exception\InvalidTokenException;
use App\Security\Domain\Repository\RefreshTokenRepository;
use App\Security\Domain\Service\LoginRateLimiter;
use App\Security\Domain\Service\RefreshTokenGenerator;
use App\Security\Domain\Service\TokenEncoderDecoder;
use App\User\Domain\ValueObject\UserId;

class RenewUserAuthenticationHandler implements TransactionalCommandHandler
{
    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private TokenEncoderDecoder $tokenEncoderDecoder,
        private LoginRateLimiter $loginRateLimiter,
        private DateTimeProvider $dateTimeProvider,
    ) {
    }

    public function __invoke(RenewUserAuthenticationCommand $command): RenewUserAuthenticationResponse
    {
        $this->loginRateLimiter->consumeRenewAttempt('ip:' . $command->ip);

        $tokenHash = hash('sha256', $command->token);
        $refreshToken = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if (!$refreshToken) {
            throw new InvalidTokenException();
        }

        if ($refreshToken->isRevoked()) {
            $this->revokeActiveTokensFor($refreshToken->getUserId());

            throw new InvalidTokenException();
        }

        if ($refreshToken->isExpired($this->dateTimeProvider->now())) {
            throw new ExpiredTokenException();
        }

        $refreshToken->updateLastUsed($this->dateTimeProvider->now());

        $newRefreshToken = $this->refreshTokenGenerator->generate($refreshToken->getUserId());

        $refreshToken->revoke($newRefreshToken->jti);
        $this->refreshTokenRepository->save($refreshToken);

        $accessToken = $this->tokenEncoderDecoder->encode([
            'id' => $refreshToken->getUserId()->value(),
        ]);

        return new RenewUserAuthenticationResponse($accessToken, $newRefreshToken->value);
    }

    private function revokeActiveTokensFor(UserId $userId): void
    {
        foreach ($this->refreshTokenRepository->findActiveByUserId($userId->value()) as $activeToken) {
            $activeToken->revoke();
            $this->refreshTokenRepository->save($activeToken);
        }
    }
}
