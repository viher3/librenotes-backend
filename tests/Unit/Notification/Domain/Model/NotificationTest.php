<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Domain\Model;

use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Core\Domain\ValueObject\Uuid;
use App\Notification\Domain\Event\NotificationFailed;
use App\Notification\Domain\Event\NotificationQueued;
use App\Notification\Domain\Event\NotificationSent;
use App\Notification\Domain\Exception\NotificationNotRetryableException;
use App\Notification\Domain\Model\Notification;
use App\Notification\Domain\Model\ValueObject\NotificationAttempts;
use App\Notification\Domain\Model\ValueObject\NotificationBody;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationId;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationPayload;
use App\Notification\Domain\Model\ValueObject\NotificationRecipient;
use App\Notification\Domain\Model\ValueObject\NotificationStatus;
use App\Notification\Domain\Model\ValueObject\NotificationSubject;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    private function createPendingNotification(): Notification
    {
        $now = new DateTimeValueObject(new \DateTimeImmutable('2026-08-27 10:00:00'));

        return new Notification(
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
    }

    public function testConstructorRecordsNotificationQueued(): void
    {
        $notification = $this->createPendingNotification();

        $events = $notification->pullDomainEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(NotificationQueued::class, $events[0]);
        self::assertSame(NotificationStatus::PENDING, $notification->getStatus()->value());
        self::assertSame(0, $notification->getAttempts()->value());
    }

    public function testMarkAsSentTransitionsToSentAndRecordsEvent(): void
    {
        $notification = $this->createPendingNotification();
        $notification->pullDomainEvents();

        $at = new DateTimeValueObject(new \DateTimeImmutable('2026-08-27 10:01:00'));
        $notification->markAsSent(
            new NotificationSubject('Welcome'),
            new NotificationBody('Hi Alice'),
            'provider-id-1',
            $at
        );

        $events = $notification->pullDomainEvents();

        self::assertSame(NotificationStatus::SENT, $notification->getStatus()->value());
        self::assertSame('provider-id-1', $notification->getProviderMessageId());
        self::assertSame('Welcome', $notification->getSubject()?->value());
        self::assertCount(1, $events);
        self::assertInstanceOf(NotificationSent::class, $events[0]);
    }

    public function testMarkAsFailedIncrementsAttemptsAndRecordsEvent(): void
    {
        $notification = $this->createPendingNotification();
        $notification->pullDomainEvents();

        $at = new DateTimeValueObject(new \DateTimeImmutable('2026-08-27 10:01:00'));
        $notification->markAsFailed(null, null, 'SMTP timeout', $at);

        $events = $notification->pullDomainEvents();

        self::assertSame(NotificationStatus::FAILED, $notification->getStatus()->value());
        self::assertSame(1, $notification->getAttempts()->value());
        self::assertSame('SMTP timeout', $notification->getLastError());
        self::assertCount(1, $events);
        self::assertInstanceOf(NotificationFailed::class, $events[0]);
    }

    public function testRetryFromFailedGoesBackToPendingAndRecordsEvent(): void
    {
        $notification = $this->createPendingNotification();
        $notification->pullDomainEvents();

        $notification->markAsFailed(null, null, 'SMTP timeout', new DateTimeValueObject(new \DateTimeImmutable('2026-08-27 10:01:00')));
        $notification->pullDomainEvents();

        $retryAt = new DateTimeValueObject(new \DateTimeImmutable('2026-08-27 10:05:00'));
        $notification->retry($retryAt);

        $events = $notification->pullDomainEvents();

        self::assertSame(NotificationStatus::PENDING, $notification->getStatus()->value());
        self::assertNull($notification->getLastError());
        self::assertCount(1, $events);
        self::assertInstanceOf(NotificationQueued::class, $events[0]);
    }

    public function testRetryFromPendingStatusThrows(): void
    {
        $notification = $this->createPendingNotification();

        $this->expectException(NotificationNotRetryableException::class);

        $notification->retry(new DateTimeValueObject(new \DateTimeImmutable()));
    }

    public function testRetryFromSentStatusThrows(): void
    {
        $notification = $this->createPendingNotification();
        $notification->pullDomainEvents();

        $notification->markAsSent(
            new NotificationSubject('Welcome'),
            new NotificationBody('Hi'),
            null,
            new DateTimeValueObject(new \DateTimeImmutable())
        );

        $this->expectException(NotificationNotRetryableException::class);

        $notification->retry(new DateTimeValueObject(new \DateTimeImmutable()));
    }
}
