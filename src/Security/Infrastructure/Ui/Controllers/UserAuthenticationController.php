<?php

namespace App\Security\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Security\Application\UserAuthentication\UserAuthenticationCommand;
use App\Security\Domain\Exception\BadCredentialsException;
use App\Security\Domain\Exception\InactiveUserException;
use App\Security\Domain\Exception\TooManyAttemptsException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserAuthenticationController extends BaseApiController
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
        $authenticationResponse = $this->dispatch(
            new UserAuthenticationCommand(
                email: $body['email'],
                password: $body['password'],
                ip: $request->getClientIp() ?? 'unknown'
            )
        );

        return new JsonResponse($authenticationResponse->getResult());
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
                "password": {
                  "type": "string",
                  "minLength": 8
                }
              },
              "required": ["email", "password"]
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [
            BadCredentialsException::class => Response::HTTP_UNAUTHORIZED,
            InactiveUserException::class => Response::HTTP_FORBIDDEN,
            TooManyAttemptsException::class => Response::HTTP_TOO_MANY_REQUESTS,
        ];
    }
}
