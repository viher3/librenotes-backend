<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\ChangePassword\ChangePasswordCommand;
use App\User\Domain\Exception\ChangePasswordRateLimited;
use App\User\Domain\Exception\ErrorUserChangePassword;
use App\User\Domain\Exception\InvalidNewPassword;
use App\User\Domain\Exception\InvalidUserPassword;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Exception\PasswordUnchanged;
use App\User\Domain\ValueObject\Password;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Self-service password change: the user is always the authenticated one and must prove
 * knowledge of the current password.
 */
class ChangeCurrentUserPasswordController extends BaseApiController
{
    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request): Response
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $body = $this->getParams($request);

        $this->dispatchTransactional(
            new ChangePasswordCommand(
                userId: $this->currentUserId(),
                currentPassword: $body['current_password'],
                newPassword: $body['new_password']
            )
        );

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function getValidationSchema(): string
    {
        $min = Password::MIN_LENGTH;
        $max = Password::MAX_LENGTH;

        return <<<JSON
            {
              "type": "object",
              "properties": {
                "current_password": {
                  "type": "string",
                  "minLength": 1,
                  "maxLength": {$max}
                },
                "new_password": {
                  "type": "string",
                  "minLength": {$min},
                  "maxLength": {$max}
                }
              },
              "required": ["current_password", "new_password"],
              "additionalProperties": false
            }
            JSON;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            InvalidUserPassword::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            InvalidNewPassword::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            PasswordUnchanged::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            NotFoundUser::class => Response::HTTP_NOT_FOUND,
            ChangePasswordRateLimited::class => Response::HTTP_TOO_MANY_REQUESTS,
            ErrorUserChangePassword::class => Response::HTTP_INTERNAL_SERVER_ERROR,
        ];
    }
}
