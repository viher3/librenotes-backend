<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notification\Application\UpdateTemplate\UpdateNotificationTemplateCommand;
use App\Notification\Domain\Exception\NotificationTemplateNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateNotificationTemplateController extends BaseApiController
{
    public function __invoke(string $id, Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(new UpdateNotificationTemplateCommand(
            id: $id,
            subject: $body['subject'] ?? null,
            body: $body['body'] ?? null,
            isActive: $body['is_active'] ?? null,
        ));

        return new JsonResponse([]);
    }

    private function getValidationSchema(): string
    {
        return <<<'JSON'
            {
              "type": "array",
              "properties": {
                "subject": {
                  "type": "string"
                },
                "body": {
                  "type": "string"
                },
                "is_active": {
                  "type": "boolean"
                }
              }
            }
            JSON;
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
