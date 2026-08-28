<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Sender;

use App\Notification\Domain\Exception\NotificationDeliveryFailedException;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationRecipient;
use App\Notification\Domain\Service\NotificationSender;
use App\Notification\Domain\Service\RenderedNotification;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class SymfonyMailerNotificationSender implements NotificationSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
        private readonly string $fromName
    ) {
    }

    public function channel(): NotificationChannel
    {
        return new NotificationChannel(NotificationChannel::EMAIL);
    }

    public function send(NotificationRecipient $recipient, RenderedNotification $message): string
    {
        // Symfony\Mailer\MailerInterface::send() returns void, so the transport never
        // hands back a provider-assigned id here. We stamp our own Message-ID before
        // sending so providerMessageId still has a stable value to store and search on.
        $messageId = sprintf('%s@notification', RamseyUuid::uuid4()->toString());

        $email = (new Email())
            ->from(sprintf('%s <%s>', $this->fromName, $this->fromAddress))
            ->to($recipient->value())
            ->subject($message->subject)
            ->html($message->body)
            ->text(strip_tags($message->body));

        $email->getHeaders()->addIdHeader('Message-ID', $messageId);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            throw new NotificationDeliveryFailedException(NotificationChannel::EMAIL, $exception->getMessage());
        }

        return $messageId;
    }
}
