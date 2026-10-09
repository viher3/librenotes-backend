<?php

declare(strict_types=1);

namespace App\Notes\Application\Search;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Exception\SearchQueryTooShort;
use App\Notes\Domain\Repository\NoteListCriteria;
use App\Notes\Domain\Repository\SearchCriteria;
use App\Notes\Domain\Repository\SearchRepository;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\Tags;

class SearchHandler implements QueryHandler
{
    private const int MAX_TERMS = 8;

    public function __construct(
        private SearchRepository $searchRepository
    ) {
    }

    public function __invoke(SearchQuery $query): SearchResponse
    {
        $q = trim($query->q);

        if (mb_strlen($q) < SearchQueryTooShort::MIN_LENGTH) {
            throw new SearchQueryTooShort();
        }

        $terms = array_slice(
            array_values(array_unique(preg_split('/\s+/u', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY) ?: [])),
            0,
            self::MAX_TERMS
        );
        $size = max(1, min($query->size ?? NoteListCriteria::DEFAULT_SIZE, NoteListCriteria::MAX_SIZE));
        $page = max(1, $query->page ?? 1);

        $result = $this->searchRepository->search(
            new SearchCriteria(
                ownerId: new OwnerId($query->ownerId),
                terms: $terms,
                types: $query->types === [] ? SearchCriteria::TYPES : $query->types,
                tag: $query->tag !== null ? Tags::normalize($query->tag) : null,
                page: $page,
                size: $size
            )
        );

        return new SearchResponse($result['items'], $result['total'], $page, $size);
    }
}
