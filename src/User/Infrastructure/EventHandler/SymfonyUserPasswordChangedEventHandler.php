<?php

namespace App\User\Infrastructure\EventHandler;

use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\User\Domain\Event\UserPasswordChanged;
use App\User\Domain\EventHandler\UserPasswordChangedEventHandler;
use App\User\Domain\Service\UserNotifier;
use Psr\Log\LoggerInterface;

/**
 * Sends the "your password was changed" security notice. A delivery problem is logged, never
 * propagated: the password change has already happened and must not depend on the mail channel.
 */
class SymfonyUserPasswordChangedEventHandler implements UserPasswordChangedEventHandler
{
    public function __construct(
        private UserNotifier $userNotifier,
        private LoggerInterface $logger,
        private bool $emailsEnabled
    )
    {
    }

    public function __invoke(UserPasswordChanged $event): void
    {
        if (!$this->emailsEnabled) {
            return;
        }

        try {
            $this->userNotifier->sendPasswordChangedEmail(new EmailValueObject($event->email()), $event->locale());
        } catch (\Throwable $e) {
            $this->logger->error('Error sending the password changed email: ' . $e->getMessage());
        }
    }
}
