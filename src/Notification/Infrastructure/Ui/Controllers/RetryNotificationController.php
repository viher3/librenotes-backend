<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notification\Application\Retry\RetryNotificationCommand;
use App\Notification\Domain\Exception\NotificationNotFoundException;
use App\Notification\Domain\Exception\NotificationNotRetryableException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class RetryNotificationController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        $this->dispatchTransactional(new RetryNotificationCommand($id));

        return new JsonResponse([]);
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            NotificationNotFoundException::class => Response::HTTP_NOT_FOUND,
            NotificationNotRetryableException::class => Response::HTTP_CONFLICT,
        ];
    }
}
