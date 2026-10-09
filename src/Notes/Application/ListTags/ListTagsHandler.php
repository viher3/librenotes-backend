<?php

declare(strict_types=1);

namespace App\Notes\Application\ListTags;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Repository\TagRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class ListTagsHandler implements QueryHandler
{
    public function __construct(
        private TagRepository $tagRepository
    ) {
    }

    public function __invoke(ListTagsQuery $query): ListTagsResponse
    {
        return new ListTagsResponse($this->tagRepository->countByOwner(new OwnerId($query->ownerId)));
    }
}
