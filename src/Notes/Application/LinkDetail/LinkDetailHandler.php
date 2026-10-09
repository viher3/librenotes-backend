<?php

declare(strict_types=1);

namespace App\Notes\Application\LinkDetail;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class LinkDetailHandler implements QueryHandler
{
    public function __construct(
        private LinkRepository $linkRepository
    ) {
    }

    public function __invoke(LinkDetailQuery $query): LinkDetailResponse
    {
        $ownerId = new OwnerId($query->ownerId);
        $link = $this->linkRepository->getById($query->id);

        if ($link === null || !$link->belongsTo($ownerId)) {
            throw new LinkNotFound($query->id);
        }

        return new LinkDetailResponse($link);
    }
}
