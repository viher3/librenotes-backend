<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Domain\Model\ValueObject;

use App\Notification\Domain\Exception\InvalidNotificationChannelException;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use PHPUnit\Framework\TestCase;

class NotificationChannelTest extends TestCase
{
    public function testValidChannelIsAccepted(): void
    {
        $channel = new NotificationChannel(NotificationChannel::EMAIL);

        self::assertSame('email', $channel->value());
    }

    public function testUnknownChannelThrows(): void
    {
        $this->expectException(InvalidNotificationChannelException::class);

        new NotificationChannel('carrier-pigeon');
    }
}
