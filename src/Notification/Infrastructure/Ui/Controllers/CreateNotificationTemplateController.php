<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notification\Application\CreateTemplate\CreateNotificationTemplateCommand;
use App\Notification\Domain\Exception\InvalidNotificationChannelException;
use App\Notification\Domain\Exception\InvalidNotificationTemplateKeyFormatException;
use App\Notification\Domain\Exception\NotificationTemplateAlreadyExistsException;
use App\Notification\Domain\Model\ValueObject\NotificationTemplateId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreateNotificationTemplateController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(new CreateNotificationTemplateCommand(
            id: $body['id'] ?? NotificationTemplateId::random()->value(),
            key: $body['key'],
            channel: $body['channel'],
            locale: $body['locale'],
            subject: $body['subject'],
            body: $body['body'],
            isActive: $body['is_active'] ?? true,
        ));

        return new JsonResponse([], Response::HTTP_CREATED);
    }

    private function getValidationSchema(): string
    {
        return <<<'JSON'
            {
              "type": "array",
              "properties": {
                "id": {
                  "type": "string",
                  "format": "uuid"
                },
                "key": {
                  "type": "string",
                  "minLength": 1,
                  "maxLength": 255
                },
                "channel": {
                  "type": "string",
                  "enum": ["email"]
                },
                "locale": {
                  "type": "string",
                  "minLength": 2,
                  "maxLength": 10
                },
                "subject": {
                  "type": "string"
                },
                "body": {
                  "type": "string"
                },
                "is_active": {
                  "type": "boolean"
                }
              },
              "required": ["key", "channel", "locale", "subject", "body"]
            }
            JSON;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            NotificationTemplateAlreadyExistsException::class => Response::HTTP_CONFLICT,
            InvalidNotificationTemplateKeyFormatException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidNotificationChannelException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
