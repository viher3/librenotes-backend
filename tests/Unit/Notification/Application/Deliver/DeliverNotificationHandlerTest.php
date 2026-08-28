<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Application\Deliver;

use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;
use App\Notification\Application\Deliver\DeliverNotificationCommand;
use App\Notification\Application\Deliver\DeliverNotificationHandler;
use App\Notification\Domain\Exception\NotificationDeliveryFailedException;
use App\Notification\Domain\Exception\NotificationTemplateRenderingFailedException;
use App\Notification\Domain\Model\Notification;
use App\Notification\Domain\Model\NotificationTemplate;
use App\Notification\Domain\Model\ValueObject\NotificationAttempts;
use App\Notification\Domain\Model\ValueObject\NotificationBody;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationId;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationPayload;
use App\Notification\Domain\Model\ValueObject\NotificationRecipient;
use App\Notification\Domain\Model\ValueObject\NotificationStatus;
use App\Notification\Domain\Model\ValueObject\NotificationSubject;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;
use App\Notification\Domain\Repository\NotificationRepository;
use App\Notification\Domain\Repository\NotificationTemplateRepository;
use App\Notification\Domain\Service\NotificationSender;
use App\Notification\Domain\Service\NotificationSenderLocator;
use App\Notification\Domain\Service\NotificationTemplateRenderer;
use App\Notification\Domain\Service\RenderedNotification;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DeliverNotificationHandlerTest extends TestCase
{
    private NotificationRepository $notificationRepository;
    private NotificationTemplateRepository $notificationTemplateRepository;
    private NotificationTemplateRenderer $renderer;
    private NotificationSenderLocator $senderLocator;
    private DeliverNotificationHandler $handler;

    protected function setUp(): void
    {
        $this->notificationRepository = $this->createMock(NotificationRepository::class);
        $this->notificationTemplateRepository = $this->createMock(NotificationTemplateRepository::class);
        $this->renderer = $this->createMock(NotificationTemplateRenderer::class);
        $this->senderLocator = $this->createMock(NotificationSenderLocator::class);

        $eventBus = $this->createMock(EventBus::class);

        $dateTimeProvider = $this->createMock(DateTimeProvider::class);
        $dateTimeProvider->method('now')->willReturn(new \DateTimeImmutable('2026-08-27 10:00:00'));

        $logger = $this->createMock(LoggerInterface::class);

        $this->handler = new DeliverNotificationHandler(
            $this->notificationRepository,
            $this->notificationTemplateRepository,
            $this->renderer,
            $this->senderLocator,
            $eventBus,
            $dateTimeProvider,
            $logger,
        );
    }

    private function createPendingNotification(): Notification
    {
        $now = new DateTimeValueObject(new \DateTimeImmutable('2026-08-27 09:00:00'));

        $notification = new Notification(
            id: NotificationId::random(),
            channel: new NotificationChannel(NotificationChannel::EMAIL),
            templateKey: new NotificationTemplateKey('user.welcome'),
            locale: new NotificationLocale('en'),
            recipient: new NotificationRecipient('user@example.com'),
            recipientId: Uuid::random(),
            payload: new NotificationPayload(['name' => 'Alice']),
            subject: null,
            body: null,
            status: new NotificationStatus(NotificationStatus::PENDING),
            attempts: new NotificationAttempts(0),
            lastError: null,
            providerMessageId: null,
            sentAt: null,
            createdAt: $now,
            updatedAt: $now,
        );
        $notification->pullDomainEvents();

        return $notification;
    }

    private function createActiveTemplate(): NotificationTemplate
    {
        $now = new DateTimeValueObject(new \DateTimeImmutable('2026-08-27 08:00:00'));

        return new NotificationTemplate(
            id: NotificationTemplateId::random(),
            key: new NotificationTemplateKey('user.welcome'),
            channel: new NotificationChannel(NotificationChannel::EMAIL),
            locale: new NotificationLocale('en'),
            subject: new NotificationSubject('Welcome {{ name }}'),
            body: new NotificationBody('Hi {{ name }}'),
            isActive: new BoolValueObject(true),
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function testDeliveryFailureIsCaughtAndMarksNotificationAsFailed(): void
    {
        $notification = $this->createPendingNotification();
        $template = $this->createActiveTemplate();

        $this->notificationRepository->method('findById')->willReturn($notification);
        $this->notificationTemplateRepository->method('findActiveByKeyChannelAndLocale')->willReturn($template);
        $this->renderer->method('render')->willReturn(new RenderedNotification('Welcome Alice', 'Hi Alice'));

        $sender = $this->createMock(NotificationSender::class);
        $sender->method('send')->willThrowException(new NotificationDeliveryFailedException('email', 'SMTP timeout'));
        $this->senderLocator->method('forChannel')->willReturn($sender);

        $this->notificationRepository->expects(self::once())->method('save')->with($notification);

        $this->handler->__invoke(new DeliverNotificationCommand($notification->getId()->value()));

        self::assertSame(NotificationStatus::FAILED, $notification->getStatus()->value());
        self::assertSame(1, $notification->getAttempts()->value());
        self::assertStringContainsString('SMTP timeout', (string) $notification->getLastError());
    }

    public function testMissingTemplateMarksNotificationAsFailedWithoutThrowing(): void
    {
        $notification = $this->createPendingNotification();

        $this->notificationRepository->method('findById')->willReturn($notification);
        $this->notificationTemplateRepository->method('findActiveByKeyChannelAndLocale')->willReturn(null);

        $this->notificationRepository->expects(self::once())->method('save')->with($notification);

        $this->handler->__invoke(new DeliverNotificationCommand($notification->getId()->value()));

        self::assertSame(NotificationStatus::FAILED, $notification->getStatus()->value());
    }

    public function testRenderingFailureMarksNotificationAsFailed(): void
    {
        $notification = $this->createPendingNotification();
        $template = $this->createActiveTemplate();

        $this->notificationRepository->method('findById')->willReturn($notification);
        $this->notificationTemplateRepository->method('findActiveByKeyChannelAndLocale')->willReturn($template);
        $this->renderer->method('render')->willThrowException(new NotificationTemplateRenderingFailedException('user.welcome', 'sandbox violation'));

        $this->notificationRepository->expects(self::once())->method('save')->with($notification);

        $this->handler->__invoke(new DeliverNotificationCommand($notification->getId()->value()));

        self::assertSame(NotificationStatus::FAILED, $notification->getStatus()->value());
    }

    public function testSuccessfulDeliveryMarksNotificationAsSent(): void
    {
        $notification = $this->createPendingNotification();
        $template = $this->createActiveTemplate();

        $this->notificationRepository->method('findById')->willReturn($notification);
        $this->notificationTemplateRepository->method('findActiveByKeyChannelAndLocale')->willReturn($template);
        $this->renderer->method('render')->willReturn(new RenderedNotification('Welcome Alice', 'Hi Alice'));

        $sender = $this->createMock(NotificationSender::class);
        $sender->method('send')->willReturn('provider-id-123');
        $this->senderLocator->method('forChannel')->willReturn($sender);

        $this->notificationRepository->expects(self::once())->method('save')->with($notification);

        $this->handler->__invoke(new DeliverNotificationCommand($notification->getId()->value()));

        self::assertSame(NotificationStatus::SENT, $notification->getStatus()->value());
        self::assertSame('provider-id-123', $notification->getProviderMessageId());
    }
}
