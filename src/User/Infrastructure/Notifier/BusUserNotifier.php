<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Notifier;

use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\Core\Domain\ValueObject\Uuid;
use App\Core\Infrastructure\Bus\Command\SymfonyTransactionalCommandBus;
use App\Notification\Application\Send\SendNotificationCommand;
use App\User\Domain\Service\UserNotifier;

/**
 * Fetcher-shaped adapter: User owns this interface in its own vocabulary, and this
 * class is the only place in the module that knows Notification exists. It dispatches
 * SendNotificationCommand — a bus-only Application use case of the Notification module,
 * with no controller of its own — through the transactional command bus, exactly like
 * Configuration's GetConfigurationValueQuery is meant to be consumed from other modules.
 */
final class BusUserNotifier implements UserNotifier
{
    private const ACTIVATION_TEMPLATE_KEY = 'user.activation';
    private const PASSWORD_CHANGED_TEMPLATE_KEY = 'user.password_changed';

    public function __construct(
        private readonly SymfonyTransactionalCommandBus $commandBus,
        private readonly string $frontendUrl
    ) {
    }

    public function sendActivationEmail(EmailValueObject $email, string $activationToken): void
    {
        $this->commandBus->__invoke(new SendNotificationCommand(
            id: Uuid::random()->value(),
            channel: 'email',
            templateKey: self::ACTIVATION_TEMPLATE_KEY,
            recipient: $email->value(),
            payload: [
                'activation_link' => rtrim($this->frontendUrl, '/') . '/activate-account?token=' . $activationToken,
            ],
        ));
    }

    public function sendPasswordChangedEmail(EmailValueObject $email, string $locale): void
    {
        $this->commandBus->__invoke(new SendNotificationCommand(
            id: Uuid::random()->value(),
            channel: 'email',
            templateKey: self::PASSWORD_CHANGED_TEMPLATE_KEY,
            recipient: $email->value(),
            locale: $locale,
        ));
    }
}
