<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Application\ListLinks\ListLinksQuery;
use App\Notes\Application\ListLinks\ListLinksResponse;
use App\Notes\Domain\Exception\InvalidTag;
use App\Notes\Domain\Repository\LinkListCriteria;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListLinksController extends BaseApiController
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

        /** @var ListLinksResponse $response */
        $response = $this->ask(
            new ListLinksQuery(
                ownerId: $this->currentUserId(),
                folderId: ($folder !== null && $folder !== self::ROOT) ? $folder : null,
                rootOnly: $folder === self::ROOT,
                tag: isset($params['tag']) ? (string) $params['tag'] : null,
                orderBy: $orderBy,
                orderDirection: $orderDirection,
                page: $page,
                size: $size,
                parentNoteId: isset($params['parent_note_id']) ? (string) $params['parent_note_id'] : null
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
        if (isset($params['parent_note_id']) && !preg_match(self::UUID_PATTERN, (string) $params['parent_note_id'])) {
            $errors[] = '[parent_note_id] Must be a UUID';
        }
        if (isset($params['orderBy']) && !in_array($params['orderBy'], LinkListCriteria::ORDER_FIELDS, true)) {
            $errors[] = '[orderBy] Must be one of: ' . implode(', ', LinkListCriteria::ORDER_FIELDS);
        }
        if (isset($params['orderDirection']) && !in_array(strtolower((string) $params['orderDirection']), ['asc', 'desc'], true)) {
            $errors[] = '[orderDirection] Must be "asc" or "desc"';
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
