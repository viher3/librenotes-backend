<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Ui\Controllers;

use App\Configuration\Application\Update\UpdateConfigurationCommand;
use App\Configuration\Domain\Exception\ConfigurationNotFoundException;
use App\Configuration\Domain\Exception\InvalidConfigurationTypeException;
use App\Configuration\Domain\Exception\InvalidConfigurationValueException;
use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class UpdateConfigurationController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $key, Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(new UpdateConfigurationCommand(
            key: $key,
            value: $body['value'] ?? null,
            type: $body['type'] ?? null,
            description: $body['description'] ?? null,
            isSensitive: $body['is_sensitive'] ?? null,
            changedBy: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID,
        ));

        return new JsonResponse([]);
    }

    private function getValidationSchema(): string
    {
        return <<<'JSON'
            {
              "type": "array",
              "properties": {
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
              }
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            ConfigurationNotFoundException::class => Response::HTTP_NOT_FOUND,
            InvalidConfigurationValueException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidConfigurationTypeException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
