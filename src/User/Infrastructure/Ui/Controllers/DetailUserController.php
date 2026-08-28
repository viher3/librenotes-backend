<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\User\Application\Detail\DetailUserQuery;
use App\User\Application\Detail\DetailUserResponse;
use App\User\Domain\Exception\NotFoundUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DetailUserController extends BaseApiController
{
    public function __invoke(string $id) : JsonResponse
    {
        /** @var DetailUserResponse $userResponse */
        $userResponse = $this->ask(
            new DetailUserQuery($id)
        );

        return new JsonResponse(
            data: $userResponse->getResult(),
            status: 200
        );
    }

    protected function exceptions(): array
    {
        return [
            NotFoundUser::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
