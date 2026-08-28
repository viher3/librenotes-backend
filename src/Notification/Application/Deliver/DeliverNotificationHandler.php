<?php

declare(strict_types=1);

namespace App\Notification\Application\Deliver;

use App\Core\Application\Command\CommandHandler;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notification\Domain\Exception\NotificationDeliveryFailedException;
use App\Notification\Domain\Exception\NotificationTemplateNotFoundException;
use App\Notification\Domain\Exception\NotificationTemplateRenderingFailedException;
use App\Notification\Domain\Model\ValueObject\NotificationBody;
use App\Notification\Domain\Model\ValueObject\NotificationId;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationSubject;
use App\Notification\Domain\Repository\NotificationRepository;
use App\Notification\Domain\Repository\NotificationTemplateRepository;
use App\Notification\Domain\Service\NotificationSenderLocator;
use App\Notification\Domain\Service\NotificationTemplateRenderer;
use Psr\Log\LoggerInterface;

/**
 * Never lets an exception escape. In synchronous mode this runs inside the caller's
 * transaction (e.g. SendNotificationHandler's), so a template or delivery failure must
 * be recorded as markAsFailed() rather than thrown — otherwise an SMTP outage would roll
 * back whatever the caller was doing (e.g. creating a user). See the module design's
 * "Flujo y la frontera síncrono → asíncrono" section for the full rationale.
 */
final class DeliverNotificationHandler implements CommandHandler
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationTemplateRepository $notificationTemplateRepository,
        private readonly NotificationTemplateRenderer $renderer,
        private readonly NotificationSenderLocator $senderLocator,
        private readonly EventBus $eventBus,
        private readonly DateTimeProvider $dateTimeProvider,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(DeliverNotificationCommand $command): void
    {
        $notification = $this->notificationRepository->findById(new NotificationId($command->id));

        if (!$notification) {
            $this->logger->error('Notification not found for delivery', ['id' => $command->id]);

            return;
        }

        $now = new DateTimeValueObject($this->dateTimeProvider->now());

        try {
            $template = $this->notificationTemplateRepository->findActiveByKeyChannelAndLocale(
                $notification->getTemplateKey(),
                $notification->getChannel(),
                $notification->getLocale()
            );

            if (!$template && $notification->getLocale()->value() !== NotificationLocale::DEFAULT) {
                $template = $this->notificationTemplateRepository->findActiveByKeyChannelAndLocale(
                    $notification->getTemplateKey(),
                    $notification->getChannel(),
                    new NotificationLocale(NotificationLocale::DEFAULT)
                );
            }

            if (!$template) {
                throw new NotificationTemplateNotFoundException($notification->getTemplateKey()->value());
            }

            $rendered = $this->renderer->render($template, $notification->getPayload()->value());

            $sender = $this->senderLocator->forChannel($notification->getChannel());
            $providerMessageId = $sender->send($notification->getRecipient(), $rendered);

            $notification->markAsSent(
                new NotificationSubject($rendered->subject),
                new NotificationBody($rendered->body),
                $providerMessageId,
                $now
            );
        } catch (
            NotificationTemplateNotFoundException
            | NotificationTemplateRenderingFailedException
            | NotificationDeliveryFailedException $exception
        ) {
            $notification->markAsFailed(null, null, $exception->getMessage(), $now);
        }

        $this->notificationRepository->save($notification);

        $this->eventBus->publish(...$notification->pullDomainEvents());
    }
}
