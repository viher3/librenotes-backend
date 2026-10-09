<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\Update\UpdateUserCommand;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Exception\UnsupportedUserLocale;
use App\User\Domain\ValueObject\UserLocale;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Self-service profile update. Only the fields listed in ALLOWED_FIELDS can be changed here;
 * email, activation state and password have their own flows.
 */
class UpdateCurrentUserController extends BaseApiController
{
    private const array ALLOWED_FIELDS = ['username', 'locale'];

    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $fields = array_intersect_key($this->getParams($request), array_flip(self::ALLOWED_FIELDS));

        if ($fields !== []) {
            $this->dispatchTransactional(
                new UpdateUserCommand(
                    id: $this->currentUserId(),
                    fields: $fields
                )
            );
        }

        return new JsonResponse([]);
    }

    private function getValidationSchema(): string
    {
        $locales = json_encode(UserLocale::SUPPORTED);

        return <<<JSON
            {
              "type": "array",
              "properties": {
                "username": {
                  "type": "string",
                  "minLength": 3,
                  "maxLength": 20
                },
                "locale": {
                  "type": "string",
                  "enum": {$locales}
                }
              }
            }
            JSON;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            NotFoundUser::class => Response::HTTP_NOT_FOUND,
            UnsupportedUserLocale::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
