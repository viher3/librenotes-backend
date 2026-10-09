<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\ChangePassword\ChangePasswordCommand;
use App\User\Domain\Exception\ErrorUserChangePassword;
use App\User\Domain\Exception\InvalidUserPassword;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChangeUserPasswordController extends BaseApiController
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
            new ChangePasswordCommand(
                userId: $id,
                oldPassword: $body['currentPassword'],
                newPassword: $body['newPassword']
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
                "currentPassword": {
                  "type": "string",
                  "minLength": 8,
                  "maxLength": 20
                },
                "newPassword": {
                  "type": "string",
                  "minLength": 8,
                  "maxLength": 20
                }
              },
              "required": ["currentPassword", "newPassword"]
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            InvalidUserPassword::class => Response::HTTP_BAD_REQUEST,
            ErrorUserChangePassword::class => Response::HTTP_INTERNAL_SERVER_ERROR
        ];
    }
}
