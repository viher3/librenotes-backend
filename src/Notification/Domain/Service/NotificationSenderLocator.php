<?php

declare(strict_types=1);

namespace App\Notification\Domain\Service;

use App\Notification\Domain\Exception\UnsupportedNotificationChannelException;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;

interface NotificationSenderLocator
{
    /**
     * @throws UnsupportedNotificationChannelException
     */
    public function forChannel(NotificationChannel $channel): NotificationSender;
}
