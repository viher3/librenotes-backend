<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Core\Domain\Exception\InvalidPartialUpdateFieldsException;
use App\User\Application\Update\UpdateUserCommand;
use App\User\Domain\Exception\UnsupportedUserLocale;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserController extends BaseApiController
{
    /**
     * @throws \Throwable
     */
    public function __invoke(string $id, Request $request): JsonResponse
    {
        if($badResponse = $this->validateInputData($this->getValidationSchema(), $request)){
            return $badResponse;
        }

        $body = $this->getParams($request);
        $this->dispatchTransactional(
            new UpdateUserCommand(
                id: $id,
                fields: $body
            )
        );

        return new JsonResponse([]);
    }

    private function getValidationSchema(): string
    {
        return <<<'JSON'
            {
              "type": "array",
              "properties": {
                "email": {
                  "type": "string",
                  "format": "email"
                },
                "username": {
                  "type": "string",
                  "minLength": 3,
                  "maxLength": 20
                },
                "locale": {
                  "type": "string",
                  "enum": ["en", "es"]
                }
              }
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            UnsupportedUserLocale::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidPartialUpdateFieldsException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
