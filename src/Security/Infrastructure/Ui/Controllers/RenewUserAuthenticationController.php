<?php

namespace App\Security\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Security\Application\RenewUserAuthentication\RenewUserAuthenticationCommand;
use App\Security\Domain\Exception\ExpiredTokenException;
use App\Security\Domain\Exception\InvalidTokenException;
use App\Security\Domain\Exception\TooManyAttemptsException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RenewUserAuthenticationController extends BaseApiController
{
    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request) : JsonResponse
    {
        if($badResponse = $this->validateInputData($this->getValidationSchema(), $request)){
            return $badResponse;
        }

        $body = $this->getParams($request);
        $renewResponse = $this->dispatchTransactional(
            new RenewUserAuthenticationCommand(
                token: $body['token'],
                ip: $request->getClientIp() ?? 'unknown'
            )
        );

        return new JsonResponse($renewResponse->getResult());
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

    protected function exceptions(): array
    {
        return [
            InvalidTokenException::class => Response::HTTP_UNAUTHORIZED,
            ExpiredTokenException::class => Response::HTTP_UNAUTHORIZED,
            TooManyAttemptsException::class => Response::HTTP_TOO_MANY_REQUESTS,
        ];
    }
}
