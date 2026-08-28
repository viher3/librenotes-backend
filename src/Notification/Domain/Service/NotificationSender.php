<?php

declare(strict_types=1);

namespace App\Notification\Domain\Service;

use App\Notification\Domain\Exception\NotificationDeliveryFailedException;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationRecipient;

interface NotificationSender
{
    public function channel(): NotificationChannel;

    /**
     * @throws NotificationDeliveryFailedException
     * @return string|null the provider's message id, if it returns one
     */
    public function send(NotificationRecipient $recipient, RenderedNotification $message): ?string;
}
