<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\Create\CreateUserCommand;
use App\User\Domain\Exception\UserEmailAlreadyExists;
use App\User\Domain\ValueObject\UserId;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateUserController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        if($badResponse = $this->validateInputData($this->getValidationSchema(), $request)){
            return $badResponse;
        }

        $body = $this->getParams($request);
        $this->dispatchTransactional(
            new CreateUserCommand(
                id: $body['id'] ?? UserId::random()->value(),
                email: $body['email'],
                password: $body['password'],
                username: $body['username'] ?? null,
                isEmailValidated: false
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
                "id": {
                  "type": "string",
                  "format": "uuid"
                },
                "email": {
                  "type": "string",
                  "format": "email"
                },
                "password": {
                  "type": "string",
                  "minLength": 8,
                  "maxLength": 44
                },
                "username": {
                  "type": ["string", "null"],
                  "minLength": 3,
                  "maxLength": 20
                }
              },
              "required": ["email", "password"]
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            UserEmailAlreadyExists::class => Response::HTTP_CONFLICT,
        ];
    }
}
