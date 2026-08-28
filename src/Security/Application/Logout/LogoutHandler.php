<?php

declare(strict_types=1);

namespace App\Security\Application\Logout;

use App\Core\Application\Command\CommandHandler;
use App\Security\Domain\Repository\RefreshTokenRepository;

class LogoutHandler implements CommandHandler
{
    public function __construct(
        private RefreshTokenRepository $refreshTokenRepository,
    ) {
    }

    public function __invoke(LogoutCommand $command): void
    {
        $tokenHash = hash('sha256', $command->token);
        $refreshToken = $this->refreshTokenRepository->findByTokenHash($tokenHash);

        if (!$refreshToken || $refreshToken->isRevoked()) {
            return;
        }

        $refreshToken->revoke();
        $this->refreshTokenRepository->save($refreshToken);
    }
}
