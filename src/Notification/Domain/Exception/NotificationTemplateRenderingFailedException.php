<?php

declare(strict_types=1);

namespace App\Notification\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

final class NotificationTemplateRenderingFailedException extends DomainError
{
    public function __construct(
        private readonly string $templateKey,
        string $reason
    ) {
        parent::__construct(sprintf(
            'Failed to render notification template "%s": %s',
            $this->templateKey,
            $reason
        ));
    }

    public function errorCode(): string
    {
        return 'notification.template_rendering_failed';
    }

    /**
     * @return array<string, string>
     */
    public function params(): array
    {
        return [
            'template_key' => $this->templateKey,
        ];
    }
}
