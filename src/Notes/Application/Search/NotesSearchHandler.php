<?php

declare(strict_types=1);

namespace App\Notes\Application\Search;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Model\Link;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\SearchResultType;

class NotesSearchHandler implements QueryHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private FolderRepository $folderRepository,
        private AttachmentRepository $attachmentRepository,
        private LinkRepository $linkRepository
    ) {
    }

    public function __invoke(NotesSearchQuery $query): NotesSearchResponse
    {
        $ownerId = new OwnerId($query->ownerId);
        $types = $this->resolveTypes($query->types);

        // A single requested type gets a real page; a mixed/global search returns
        // a capped preview per group instead of a cross-type page (relevance scores
        // from FULLTEXT and from the LIKE-based matches aren't comparable).
        $page = count($types) === 1 ? $query->page : 1;

        return new NotesSearchResponse(
            query: $query->term,
            notes: $this->searchGroup(
                $types,
                SearchResultType::Note,
                fn () => $this->noteRepository->searchByTerm($query->term, $ownerId, $page, $query->perPage),
                fn () => $this->noteRepository->searchByTermCount($query->term, $ownerId)
            ),
            folders: $this->searchGroup(
                $types,
                SearchResultType::Folder,
                fn () => $this->folderRepository->searchByTerm($query->term, $ownerId, $page, $query->perPage),
                fn () => $this->folderRepository->searchByTermCount($query->term, $ownerId)
            ),
            attachments: $this->searchGroup(
                $types,
                SearchResultType::Attachment,
                fn () => $this->attachmentRepository->searchByTerm($query->term, $ownerId, $page, $query->perPage),
                fn () => $this->attachmentRepository->searchByTermCount($query->term, $ownerId)
            ),
            links: $this->searchGroup(
                $types,
                SearchResultType::Link,
                fn () => $this->linkRepository->searchByTerm($query->term, $ownerId, $page, $query->perPage),
                fn () => $this->linkRepository->searchByTermCount($query->term, $ownerId)
            )
        );
    }

    /**
     * @param SearchResultType[] $types
     * @return array{items: list<Note|Folder|Attachment|Link>, total: int}
     */
    private function searchGroup(array $types, SearchResultType $type, \Closure $items, \Closure $total): array
    {
        if (!in_array($type, $types, true)) {
            return ['items' => [], 'total' => 0];
        }

        return ['items' => $items(), 'total' => $total()];
    }

    /**
     * @param string[] $rawTypes
     * @return SearchResultType[]
     */
    private function resolveTypes(array $rawTypes): array
    {
        if ($rawTypes === []) {
            return SearchResultType::cases();
        }

        $types = array_filter(array_map(
            static fn (string $type) => SearchResultType::tryFrom($type),
            $rawTypes
        ));

        return array_values(array_unique($types, SORT_REGULAR));
    }
}
