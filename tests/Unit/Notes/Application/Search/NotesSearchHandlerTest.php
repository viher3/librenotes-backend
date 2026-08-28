<?php

declare(strict_types=1);

namespace App\Tests\Unit\Notes\Application\Search;

use App\Notes\Application\Search\NotesSearchHandler;
use App\Notes\Application\Search\NotesSearchQuery;
use App\Notes\Application\Search\NotesSearchResponse;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\NoteContent;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\NoteTitle;
use App\Notes\Domain\ValueObject\OwnerId;
use PHPUnit\Framework\TestCase;

class NotesSearchHandlerTest extends TestCase
{
    private NoteRepository $noteRepository;
    private FolderRepository $folderRepository;
    private AttachmentRepository $attachmentRepository;
    private NotesSearchHandler $handler;

    protected function setUp(): void
    {
        $this->noteRepository = $this->createMock(NoteRepository::class);
        $this->folderRepository = $this->createMock(FolderRepository::class);
        $this->attachmentRepository = $this->createMock(AttachmentRepository::class);

        $this->handler = new NotesSearchHandler(
            $this->noteRepository,
            $this->folderRepository,
            $this->attachmentRepository
        );
    }

    public function testSearchingAllTypesQueriesEveryRepositoryAtPageOne(): void
    {
        $ownerId = OwnerId::random()->value();
        $note = new Note(
            NoteId::random(),
            new NoteTitle('Invoice March'),
            new NoteContent('some content'),
            null,
            new OwnerId($ownerId),
            new \DateTimeImmutable()
        );

        $this->noteRepository->expects($this->once())
            ->method('searchByTerm')
            ->with('invoice', $this->isInstanceOf(OwnerId::class), 1, 10)
            ->willReturn([$note]);
        $this->noteRepository->method('searchByTermCount')->willReturn(1);

        $this->folderRepository->expects($this->once())
            ->method('searchByTerm')
            ->with('invoice', $this->isInstanceOf(OwnerId::class), 1, 10)
            ->willReturn([]);
        $this->folderRepository->method('searchByTermCount')->willReturn(0);

        $this->attachmentRepository->expects($this->once())
            ->method('searchByTerm')
            ->with('invoice', $this->isInstanceOf(OwnerId::class), 1, 10)
            ->willReturn([]);
        $this->attachmentRepository->method('searchByTermCount')->willReturn(0);

        // A page other than 1 is requested, but a multi-type (global) search
        // has no cross-type ranking, so it must always query at page 1.
        $response = ($this->handler)(new NotesSearchQuery(
            term: 'invoice',
            ownerId: $ownerId,
            types: [],
            page: 3,
            perPage: 10
        ));

        $this->assertInstanceOf(NotesSearchResponse::class, $response);
        $result = $response->getResult();

        $this->assertSame('invoice', $result['meta']['query']);
        $this->assertSame(1, $result['data']['notes']['total']);
        $this->assertCount(1, $result['data']['notes']['items']);
        $this->assertSame('Invoice March', $result['data']['notes']['items'][0]['title']);
        $this->assertSame(0, $result['data']['folders']['total']);
        $this->assertSame(0, $result['data']['attachments']['total']);
    }

    public function testSearchingASingleTypeOnlyQueriesThatRepositoryAndKeepsThePage(): void
    {
        $ownerId = OwnerId::random()->value();

        $this->noteRepository->expects($this->once())
            ->method('searchByTerm')
            ->with('invoice', $this->isInstanceOf(OwnerId::class), 3, 5)
            ->willReturn([]);
        $this->noteRepository->method('searchByTermCount')->willReturn(0);

        $this->folderRepository->expects($this->never())->method('searchByTerm');
        $this->attachmentRepository->expects($this->never())->method('searchByTerm');

        $response = ($this->handler)(new NotesSearchQuery(
            term: 'invoice',
            ownerId: $ownerId,
            types: ['note'],
            page: 3,
            perPage: 5
        ));

        $result = $response->getResult();
        $this->assertSame(0, $result['data']['folders']['total']);
        $this->assertSame(0, $result['data']['attachments']['total']);
    }

    public function testUnknownTypeTokensMatchNothing(): void
    {
        $this->noteRepository->expects($this->never())->method('searchByTerm');
        $this->folderRepository->expects($this->never())->method('searchByTerm');
        $this->attachmentRepository->expects($this->never())->method('searchByTerm');

        $response = ($this->handler)(new NotesSearchQuery(
            term: 'invoice',
            ownerId: OwnerId::random()->value(),
            types: ['bogus']
        ));

        $result = $response->getResult();
        $this->assertSame(0, $result['data']['notes']['total']);
        $this->assertSame(0, $result['data']['folders']['total']);
        $this->assertSame(0, $result['data']['attachments']['total']);
    }
}
