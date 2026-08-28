<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notification\Infrastructure\Template;

use App\Core\Domain\ValueObject\BoolValueObject;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Notification\Domain\Exception\NotificationTemplateRenderingFailedException;
use App\Notification\Domain\Model\NotificationTemplate;
use App\Notification\Domain\Model\ValueObject\NotificationBody;
use App\Notification\Domain\Model\ValueObject\NotificationChannel;
use App\Notification\Domain\Model\ValueObject\NotificationLocale;
use App\Notification\Domain\Model\ValueObject\NotificationSubject;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateKey;
use App\Notification\Infrastructure\Template\TwigNotificationTemplateRenderer;
use PHPUnit\Framework\TestCase;

class TwigNotificationTemplateRendererTest extends TestCase
{
    private function template(string $subject, string $body): NotificationTemplate
    {
        $now = new DateTimeValueObject(new \DateTimeImmutable());

        return new NotificationTemplate(
            id: NotificationTemplateId::random(),
            key: new NotificationTemplateKey('user.welcome'),
            channel: new NotificationChannel(NotificationChannel::EMAIL),
            locale: new NotificationLocale('en'),
            subject: new NotificationSubject($subject),
            body: new NotificationBody($body),
            isActive: new BoolValueObject(true),
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function testRendersVariablesFromPayload(): void
    {
        $renderer = new TwigNotificationTemplateRenderer();

        $rendered = $renderer->render(
            $this->template('Welcome {{ name }}', 'Hi {{ name }}, enjoy!'),
            ['name' => 'Alice']
        );

        self::assertSame('Welcome Alice', $rendered->subject);
        self::assertSame('Hi Alice, enjoy!', $rendered->body);
    }

    public function testRejectsDisallowedTag(): void
    {
        $renderer = new TwigNotificationTemplateRenderer();

        $this->expectException(NotificationTemplateRenderingFailedException::class);

        $renderer->render($this->template('{% set x = 1 %}{{ x }}', 'body'), []);
    }

    public function testRejectsDisallowedFilter(): void
    {
        $renderer = new TwigNotificationTemplateRenderer();

        $this->expectException(NotificationTemplateRenderingFailedException::class);

        $renderer->render($this->template('subject', '{{ name|raw }}'), ['name' => '<b>x</b>']);
    }
}
