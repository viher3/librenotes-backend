<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Ui\Controllers;

use App\Configuration\Application\Create\CreateConfigurationCommand;
use App\Configuration\Domain\Exception\ConfigurationKeyAlreadyExistsException;
use App\Configuration\Domain\Exception\InvalidConfigurationKeyFormatException;
use App\Configuration\Domain\Exception\InvalidConfigurationTypeException;
use App\Configuration\Domain\Exception\InvalidConfigurationValueException;
use App\Configuration\Domain\Model\ValueObject\ConfigurationId;
use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CreateConfigurationController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(new CreateConfigurationCommand(
            id: $body['id'] ?? ConfigurationId::random()->value(),
            key: $body['key'],
            value: $body['value'],
            type: $body['type'],
            description: $body['description'] ?? null,
            isSensitive: $body['is_sensitive'] ?? false,
            changedBy: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID,
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
                "value": {
                  "type": "string"
                },
                "type": {
                  "type": "string",
                  "enum": ["string", "integer", "boolean", "json"]
                },
                "description": {
                  "type": ["string", "null"]
                },
                "is_sensitive": {
                  "type": "boolean"
                }
              },
              "required": ["key", "value", "type"]
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            ConfigurationKeyAlreadyExistsException::class => Response::HTTP_CONFLICT,
            InvalidConfigurationValueException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidConfigurationTypeException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidConfigurationKeyFormatException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
