<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\ListNotes\ListNotesQuery;
use App\Notes\Application\ListNotes\ListNotesResponse;
use App\Notes\Domain\Exception\InvalidTag;
use App\Notes\Domain\Repository\NoteListCriteria;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListNotesController extends BaseApiController
{
    private const string ROOT = 'root';
    private const string UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    public function __invoke(Request $request): JsonResponse
    {
        $params = $this->getParams($request);
        $errors = $this->validate($params);

        if ($errors !== []) {
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        [$page, $size, $orderBy, $orderDirection] = $this->getSearchParams($params);
        $folder = $params['folder_id'] ?? null;

        /** @var ListNotesResponse $response */
        $response = $this->ask(
            new ListNotesQuery(
                ownerId: $this->currentUserId(),
                folderId: ($folder !== null && $folder !== self::ROOT) ? $folder : null,
                rootOnly: $folder === self::ROOT,
                tag: isset($params['tag']) ? (string) $params['tag'] : null,
                pinned: isset($params['pinned']) ? filter_var($params['pinned'], FILTER_VALIDATE_BOOLEAN) : null,
                orderBy: $orderBy,
                orderDirection: $orderDirection,
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

        if (isset($params['folder_id']) && $params['folder_id'] !== self::ROOT
            && !preg_match(self::UUID_PATTERN, (string) $params['folder_id'])) {
            $errors[] = '[folder_id] Must be a UUID or "root"';
        }
        if (isset($params['orderBy']) && !in_array($params['orderBy'], NoteListCriteria::ORDER_FIELDS, true)) {
            $errors[] = '[orderBy] Must be one of: ' . implode(', ', NoteListCriteria::ORDER_FIELDS);
        }
        if (isset($params['orderDirection']) && !in_array(strtolower((string) $params['orderDirection']), ['asc', 'desc'], true)) {
            $errors[] = '[orderDirection] Must be "asc" or "desc"';
        }
        if (isset($params['pinned']) && filter_var($params['pinned'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            $errors[] = '[pinned] Must be a boolean';
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
            InvalidTag::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }
}
