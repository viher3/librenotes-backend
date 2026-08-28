<?php

declare(strict_types=1);

namespace App\Notification\Domain\Service;

use App\Notification\Domain\Exception\NotificationTemplateRenderingFailedException;
use App\Notification\Domain\Model\NotificationTemplate;

interface NotificationTemplateRenderer
{
    /**
     * @param array<string, scalar|array<mixed>|null> $variables
     * @throws NotificationTemplateRenderingFailedException
     */
    public function render(NotificationTemplate $template, array $variables): RenderedNotification;
}
