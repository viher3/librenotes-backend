<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure\Sender;

use App\Notification\Domain\Exception\UnsupportedNotificationChannelException;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Service\NotificationSender;
use App\Notification\Infrastructure\Sender\TaggedNotificationSenderLocator;
use PHPUnit\Framework\TestCase;

class TaggedNotificationSenderLocatorTest extends TestCase
{
    public function testResolvesRegisteredChannel(): void
    {
        $emailSender = $this->createMock(NotificationSender::class);
        $emailSender->method('channel')->willReturn(new NotificationChannel(NotificationChannel::EMAIL));

        $locator = new TaggedNotificationSenderLocator([$emailSender]);

        self::assertSame($emailSender, $locator->forChannel(new NotificationChannel(NotificationChannel::EMAIL)));
    }

    public function testThrowsForChannelWithoutRegisteredSender(): void
    {
        $locator = new TaggedNotificationSenderLocator([]);

        $this->expectException(UnsupportedNotificationChannelException::class);

        $locator->forChannel(new NotificationChannel(NotificationChannel::EMAIL));
    }
}
