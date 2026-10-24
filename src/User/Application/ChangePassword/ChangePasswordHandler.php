<?php

namespace App\User\Application\ChangePassword;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Bus\Event\EventBus;
use App\Security\Domain\Repository\RefreshTokenRepository;
use App\User\Domain\Exception\InvalidUserPassword;
use App\User\Domain\Service\ChangePasswordAttemptLimiter;
use App\User\Domain\Service\ChangePasswordService;
use Psr\Log\LoggerInterface;

class ChangePasswordHandler implements TransactionalCommandHandler
{
    public function __construct(
        private ChangePasswordService $changePasswordService,
        private ChangePasswordAttemptLimiter $attemptLimiter,
        private RefreshTokenRepository $refreshTokenRepository,
        private EventBus $eventBus,
        private LoggerInterface $logger
    )
    {
    }

    public function __invoke(ChangePasswordCommand $command) : void
    {
        $this->attemptLimiter->assertAllowed($command->userId);

        try {
            $user = $this->changePasswordService->changePassword(
                userId: $command->userId,
                newPassword: $command->newPassword,
                currentPassword: $command->currentPassword
            );
        } catch (InvalidUserPassword $e) {
            $this->attemptLimiter->registerFailure($command->userId);

            throw $e;
        }

        $this->attemptLimiter->reset($command->userId);

        // Access tokens die with the token version bump; refresh tokens would let the old sessions keep renewing.
        foreach ($this->refreshTokenRepository->findActiveByUserId($command->userId) as $refreshToken) {
            $refreshToken->revoke();
            $this->refreshTokenRepository->save($refreshToken);
        }

        try {
            $this->eventBus->publish(...$user->pullDomainEvents());
        } catch (\Throwable $e) {
            // The password is already changed: a failure reacting to the event (e.g. the notice email) must not undo it.
            $this->logger->error('Error publishing password changed events: ' . $e->getMessage());
        }
    }
}
