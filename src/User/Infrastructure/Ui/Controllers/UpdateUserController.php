<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\Update\UpdateUserCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
                }
              },
              "required": ["password"]
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [];
    }
}
