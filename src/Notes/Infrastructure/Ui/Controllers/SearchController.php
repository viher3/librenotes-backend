<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\Search\NotesSearchQuery;
use App\Notes\Application\Search\NotesSearchResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends BaseApiController
{
    private const string NIL_UUID = '00000000-0000-0000-0000-000000000000';

    public function __invoke(Request $request): JsonResponse
    {
        if ($badResponse = $this->validateInputData($this->getValidationSchema(), $request)) {
            return $badResponse;
        }

        $params = $this->getParams($request);
        [$page, $size] = $this->getSearchParams($params);

        /** @var NotesSearchResponse $response */
        $response = $this->ask(
            new NotesSearchQuery(
                term: $params['q'],
                ownerId: $this->getUser()?->getUserIdentifier() ?? self::NIL_UUID,
                types: $this->parseTypes($params['type'] ?? null),
                page: $page ?? 1,
                perPage: $size ?? 10
            )
        );

        return new JsonResponse($response->getResult());
    }

    /**
     * @return string[]
     */
    private function parseTypes(?string $type): array
    {
        if ($type === null || $type === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $type))));
    }

    private function getValidationSchema(): string
    {
        return <<<'JSON'
            {
              "type": "array",
              "properties": {
                "q": {
                  "type": "string",
                  "minLength": 2,
                  "maxLength": 255
                },
                "type": {
                  "type": "string"
                },
                "page": {
                  "type": ["string", "integer"]
                },
                "size": {
                  "type": ["string", "integer"]
                }
              },
              "required": ["q"]
            }
            JSON;
    }

    /**
     * @return array<class-string, int>
     */
    protected function exceptions(): array
    {
        return [];
    }
}
