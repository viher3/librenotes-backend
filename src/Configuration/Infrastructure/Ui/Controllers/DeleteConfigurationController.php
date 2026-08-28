<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Ui\Controllers;

use App\Configuration\Application\Delete\DeleteConfigurationCommand;
use App\Configuration\Domain\Exception\ConfigurationNotFoundException;
use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class DeleteConfigurationController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(string $key): JsonResponse
    {
        $this->dispatchTransactional(new DeleteConfigurationCommand(
            key: $key,
            changedBy: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID,
        ));

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    protected function exceptions(): array
    {
        return [
            ConfigurationNotFoundException::class => Response::HTTP_NOT_FOUND,
        ];
    }
}
