<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notification\Application\TemplateSearch\NotificationTemplateSearchQuery;
use App\Notification\Application\TemplateSearch\NotificationTemplateSearchResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class NotificationTemplateSearchController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $params = $this->getParams($request);

        /** @var NotificationTemplateSearchResponse $response */
        $response = $this->ask(new NotificationTemplateSearchQuery(
            $this->getFilters($request),
            ...$this->getSearchParams($params)
        ));

        return new JsonResponse($response->getResult());
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [];
    }
}
