<?php

declare(strict_types=1);

namespace App\Tests\Acceptance\Notes\Controller;

use App\Core\Infrastructure\Bus\Command\SymfonyTransactionalCommandBus;
use App\Notes\Application\CreateFolder\CreateFolderCommand;
use App\Notes\Application\CreateNote\CreateNoteCommand;
use App\Notes\Application\UploadAttachment\UploadAttachmentCommand;
use App\Notes\Domain\ValueObject\AttachmentId;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteId;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchControllerTest extends WebTestCase
{
    public function testSearchAcrossNotesFoldersAndAttachments(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);
        $ownerId = $loginService->getUserId();
        $bus = $client->getContainer()->get(SymfonyTransactionalCommandBus::class);

        $folderId = FolderId::random()->value();
        $bus->__invoke(new CreateFolderCommand(
            id: $folderId,
            name: 'Invoices 2026',
            parentFolderId: null,
            ownerId: $ownerId
        ));

        $noteId = NoteId::random()->value();
        $bus->__invoke(new CreateNoteCommand(
            id: $noteId,
            title: 'March invoice notes',
            content: 'Remember to chase the unpaid invoice from Acme.',
            folderId: null,
            ownerId: $ownerId
        ));

        $tmpFile = tempnam(sys_get_temp_dir(), 'attachment');
        file_put_contents($tmpFile, 'dummy content');
        $attachmentId = AttachmentId::random()->value();
        $bus->__invoke(new UploadAttachmentCommand(
            id: $attachmentId,
            folderId: $folderId,
            ownerId: $ownerId,
            originalFileName: 'invoice-march.pdf',
            mimeType: 'application/pdf',
            sizeBytes: filesize($tmpFile) ?: 0,
            tmpFilePath: $tmpFile
        ));
        unlink($tmpFile);

        $client->request('GET', '/search?q=invoice', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('invoice', $data['meta']['query']);

        $this->assertSame(1, $data['data']['notes']['total']);
        $this->assertSame($noteId, $data['data']['notes']['items'][0]['id']);

        $this->assertSame(1, $data['data']['folders']['total']);
        $this->assertSame($folderId, $data['data']['folders']['items'][0]['id']);

        $this->assertSame(1, $data['data']['attachments']['total']);
        $this->assertSame($attachmentId, $data['data']['attachments']['items'][0]['id']);
    }

    public function testSearchRestrictedToASingleTypePaginatesThatTypeOnly(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);
        $ownerId = $loginService->getUserId();
        $bus = $client->getContainer()->get(SymfonyTransactionalCommandBus::class);

        $bus->__invoke(new CreateFolderCommand(
            id: FolderId::random()->value(),
            name: 'Budget notes',
            parentFolderId: null,
            ownerId: $ownerId
        ));
        $bus->__invoke(new CreateNoteCommand(
            id: NoteId::random()->value(),
            title: 'Budget planning',
            content: 'Draft budget for next quarter.',
            folderId: null,
            ownerId: $ownerId
        ));

        $client->request('GET', '/search?q=budget&type=folder', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(1, $data['data']['folders']['total']);
        $this->assertSame(0, $data['data']['notes']['total']);
        $this->assertSame([], $data['data']['notes']['items']);
        $this->assertSame(0, $data['data']['attachments']['total']);
    }

    public function testSearchDoesNotLeakAnotherOwnersResults(): void
    {
        $client = static::createClient();

        $otherOwnerLogin = new LoginService();
        $client = $otherOwnerLogin->execute($client);
        $bus = $client->getContainer()->get(SymfonyTransactionalCommandBus::class);
        $bus->__invoke(new CreateNoteCommand(
            id: NoteId::random()->value(),
            title: 'Confidential roadmap',
            content: 'Not visible to other owners.',
            folderId: null,
            ownerId: $otherOwnerLogin->getUserId()
        ));

        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request('GET', '/search?q=roadmap', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(0, $data['data']['notes']['total']);
    }

    public function testSearchRejectsATermThatIsTooShort(): void
    {
        $client = static::createClient();
        $loginService = new LoginService();
        $client = $loginService->execute($client);

        $client->request('GET', '/search?q=a', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $loginService->getAccessToken(),
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSearchWithoutATokenIsRejected(): void
    {
        $client = static::createClient();

        $client->request('GET', '/search?q=invoice');

        $this->assertResponseStatusCodeSame(401);
    }
}
