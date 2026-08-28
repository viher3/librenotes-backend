<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\ActivateAccount\ActivateAccountCommand;
use App\User\Domain\Exception\ActivationTokenExpired;
use App\User\Domain\Exception\InvalidActivationToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivateAccountController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);
        $this->dispatchTransactional(
            new ActivateAccountCommand(token: $body['token'])
        );

        return new JsonResponse([]);
    }

    private function getValidationSchema(): string
    {
        return <<<'JSON'
            {
              "type": "array",
              "properties": {
                "token": {
                  "type": "string",
                  "minLength": 1
                }
              },
              "required": ["token"]
            }
            JSON;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            InvalidActivationToken::class => Response::HTTP_NOT_FOUND,
            ActivationTokenExpired::class => Response::HTTP_GONE,
        ];
    }
}
