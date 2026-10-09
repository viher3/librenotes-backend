<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\Search\SearchQuery;
use App\Notes\Application\Search\SearchResponse;
use App\Notes\Domain\Exception\SearchQueryTooShort;
use App\Notes\Domain\Repository\SearchCriteria;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends BaseApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        $params = $this->getParams($request);
        $errors = $this->validate($params);

        if ($errors !== []) {
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        [$page, $size] = $this->getSearchParams($params);

        /** @var SearchResponse $response */
        $response = $this->ask(
            new SearchQuery(
                ownerId: $this->currentUserId(),
                q: (string) $params['q'],
                types: isset($params['type']) ? array_values(array_unique(explode(',', (string) $params['type']))) : [],
                tag: isset($params['tag']) ? (string) $params['tag'] : null,
                page: $page,
                size: $size
            )
        );

        return new JsonResponse($response->getResult());
    }

    /**
     * @param array<string, mixed> $params
     * @return string[]
     */
    private function validate(array $params): array
    {
        $errors = [];

        if (!isset($params['q']) || !is_string($params['q'])) {
            $errors[] = '[q] The property q is required';
        }
        if (isset($params['type'])) {
            $invalid = array_diff(explode(',', (string) $params['type']), SearchCriteria::TYPES);
            if ($invalid !== []) {
                $errors[] = '[type] Must be a comma-separated list of: ' . implode(', ', SearchCriteria::TYPES);
            }
        }
        foreach (['page', 'size'] as $name) {
            if (isset($params[$name]) && (!ctype_digit((string) $params[$name]) || (int) $params[$name] < 1)) {
                $errors[] = "[$name] Must be a positive integer";
            }
        }

        return $errors;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [
            SearchQueryTooShort::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
