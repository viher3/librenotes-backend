<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notification\Application\Detail\NotificationDetailQuery;
use App\Notification\Application\Detail\NotificationDetailResponse;
use App\Notification\Domain\Exception\NotificationNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class NotificationDetailController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        /** @var NotificationDetailResponse $response */
        $response = $this->ask(new NotificationDetailQuery($id));

        return new JsonResponse($response->getResult());
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            NotificationNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
