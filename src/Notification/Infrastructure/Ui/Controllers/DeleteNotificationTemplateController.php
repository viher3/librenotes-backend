<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notification\Application\DeleteTemplate\DeleteNotificationTemplateCommand;
use App\Notification\Domain\Exception\NotificationTemplateNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DeleteNotificationTemplateController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        $this->dispatchTransactional(new DeleteNotificationTemplateCommand($id));

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            NotificationTemplateNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
