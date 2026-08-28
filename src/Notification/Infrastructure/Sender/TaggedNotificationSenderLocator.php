<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Sender;

use App\Notification\Domain\Exception\UnsupportedNotificationChannelException;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Service\NotificationSender;
use App\Notification\Domain\Service\NotificationSenderLocator;

final class TaggedNotificationSenderLocator implements NotificationSenderLocator
{
    /** @var array<string, NotificationSender> */
    private readonly array $sendersByChannel;

    /**
     * @param iterable<NotificationSender> $senders
     */
    public function __construct(iterable $senders)
    {
        $sendersByChannel = [];

        foreach ($senders as $sender) {
            $sendersByChannel[$sender->channel()->value()] = $sender;
        }

        $this->sendersByChannel = $sendersByChannel;
    }

    public function forChannel(NotificationChannel $channel): NotificationSender
    {
        return $this->sendersByChannel[$channel->value()] ?? throw new UnsupportedNotificationChannelException($channel->value());
    }
}
