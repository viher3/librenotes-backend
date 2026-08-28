<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Template;

use App\Notification\Domain\Exception\NotificationTemplateRenderingFailedException;
use App\Notification\Domain\Model\NotificationTemplate;
use App\Notification\Domain\Service\NotificationTemplateRenderer;
use App\Notification\Domain\Service\RenderedNotification;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;

/**
 * Renders subject/body strings pulled from the database. A template row is untrusted
 * input from the application's point of view (data an admin edited, not deployed code),
 * so this runs on a dedicated Environment — never the app's shared Twig service — sandboxed
 * globally with a tight allowlist: if/for only, a short filter list, and no method or
 * property access at all (render variables are always scalars/arrays decoded from JSON,
 * never objects, so there is nothing legitimate for such access to reach).
 */
final class TwigNotificationTemplateRenderer implements NotificationTemplateRenderer
{
    private const ALLOWED_TAGS = ['if', 'for'];
    private const ALLOWED_FILTERS = ['escape', 'e', 'upper', 'lower', 'date', 'number_format', 'default'];

    private readonly Environment $twig;

    public function __construct()
    {
        $policy = new SecurityPolicy(
            self::ALLOWED_TAGS,
            self::ALLOWED_FILTERS,
            [],
            [],
            []
        );

        $this->twig = new Environment(new ArrayLoader(), ['autoescape' => 'html']);
        $this->twig->addExtension(new SandboxExtension($policy, true));
    }

    public function render(NotificationTemplate $template, array $variables): RenderedNotification
    {
        $key = $template->getKey()->value();

        return new RenderedNotification(
            subject: $this->renderString($key, $template->getSubject()->value(), $variables),
            body: $this->renderString($key, $template->getBody()->value(), $variables),
        );
    }

    /**
     * @param array<string, scalar|array<mixed>|null> $variables
     */
    private function renderString(string $templateKey, string $source, array $variables): string
    {
        try {
            return $this->twig->createTemplate($source)->render($variables);
        } catch (TwigError $exception) {
            throw new NotificationTemplateRenderingFailedException($templateKey, $exception->getMessage());
        }
    }
}
