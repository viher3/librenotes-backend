<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notification\Application\TemplateDetail\NotificationTemplateDetailQuery;
use App\Notification\Application\TemplateDetail\NotificationTemplateDetailResponse;
use App\Notification\Domain\Exception\NotificationTemplateNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class NotificationTemplateDetailController extends BaseApiController
{
    public function __invoke(string $id): JsonResponse
    {
        /** @var NotificationTemplateDetailResponse $response */
        $response = $this->ask(new NotificationTemplateDetailQuery($id));

        return new JsonResponse($response->getResult());
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
