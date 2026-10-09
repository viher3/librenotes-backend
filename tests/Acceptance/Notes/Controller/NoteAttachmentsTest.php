<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NoteAttachmentsTest extends WebTestCase
{
    private function api(): NotesApi
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        return new NotesApi($client, $login->getAccessToken());
    }

    public function testUploadToANoteAppearsInTheNoteDetail(): void
    {
        $api = $this->api();
        $noteId = $api->createNote('With file');

        $created = $api->upload('/notes/' . $noteId . '/attachments', 'doc.txt', 'hello world');
        $this->assertSame(201, $api->status());

        $note = $api->request('GET', '/notes/' . $noteId);
        $this->assertCount(1, $note['attachments']);
        $this->assertSame($created['id'], $note['attachments'][0]['id']);
        $this->assertSame('doc.txt', $note['attachments'][0]['file_name']);
        $this->assertSame(11, $note['attachments'][0]['size_bytes']);

        $attachment = $api->request('GET', '/attachments/' . $created['id']);
        $this->assertSame($noteId, $attachment['note_id']);
        $this->assertNull($attachment['folder_id']);
    }

    public function testUploadToAMissingNoteIsNotFound(): void
    {
        $api = $this->api();

        $api->upload('/notes/00000000-0000-4000-8000-000000000000/attachments');

        $this->assertSame(404, $api->status());
    }

    public function testEmptyFilesAreRejected(): void
    {
        $api = $this->api();
        $noteId = $api->createNote('Empty');

        $body = $api->upload('/notes/' . $noteId . '/attachments', 'empty.txt', '');

        $this->assertSame(422, $api->status());
        $this->assertSame('attachment.empty', $body['code']);
    }

    public function testFilesOverTheLimitAreRejected(): void
    {
        $api = $this->api();
        $noteId = $api->createNote('Big');
        $path = tempnam(sys_get_temp_dir(), 'big');
        $handle = fopen($path, 'wb');
        ftruncate($handle, 50 * 1024 * 1024 + 1);
        fclose($handle);

        $body = $api->uploadFromPath('/notes/' . $noteId . '/attachments', $path, 'big.bin');
        unlink($path);

        $this->assertSame(422, $api->status());
        $this->assertSame('attachment.too_large', $body['code']);
        $this->assertSame(50 * 1024 * 1024, $body['params']['max_bytes']);
    }

    public function testDownloadStreamsTheFileWithSafeHeaders(): void
    {
        $api = $this->api();
        $noteId = $api->createNote('Download');
        $id = $api->upload('/notes/' . $noteId . '/attachments', 'report ñ.txt', 'file body')['id'];

        ob_start();
        $api->client()->request('GET', '/attachments/' . $id . '/content', [], [], $api->authServer());
        ob_end_clean();
        $response = $api->client()->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $this->assertSame('9', $response->headers->get('Content-Length'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringStartsWith('attachment;', $disposition);
        $this->assertStringContainsString("filename*=utf-8''report%20%C3%B1.txt", $disposition);
    }

    public function testTextFilesAreNeverServedInlineEvenIfRequested(): void
    {
        $api = $this->api();
        $noteId = $api->createNote('Inline');
        $id = $api->upload('/notes/' . $noteId . '/attachments', 'page.html', '<script>alert(1)</script>', 'text/html')['id'];

        ob_start();
        $api->client()->request('GET', '/attachments/' . $id . '/content', ['inline' => '1'], [], $api->authServer());
        ob_end_clean();

        $this->assertStringStartsWith('attachment;', $api->client()->getResponse()->headers->get('Content-Disposition'));
    }

    public function testNoteAttachmentsFollowTheNoteThroughTrashRestoreAndDeletion(): void
    {
        $api = $this->api();
        $noteId = $api->createNote('Lifecycle');
        $id = $api->upload('/notes/' . $noteId . '/attachments', 'a.txt', 'abc')['id'];

        $api->request('DELETE', '/notes/' . $noteId);
        $trash = $api->request('GET', '/trash');
        $this->assertSame([$noteId], array_column($trash['notes'], 'id'));
        $this->assertSame([], $trash['attachments'], 'attachments of a trashed note travel with it');
        $api->client()->request('GET', '/attachments/' . $id . '/content', [], [], $api->authServer());
        $this->assertSame(404, $api->status());

        $api->request('POST', '/trash/notes/' . $noteId . '/restore');
        $this->assertSame(200, $api->status());
        $note = $api->request('GET', '/notes/' . $noteId);
        $this->assertCount(1, $note['attachments']);

        $api->request('DELETE', '/notes/' . $noteId);
        $api->request('DELETE', '/trash/notes/' . $noteId);
        $this->assertSame(200, $api->status());
        $api->request('GET', '/attachments/' . $id);
        $this->assertSame(404, $api->status(), 'permanently deleting a note removes its attachments');
    }

    public function testAnAttachmentTrashedOnItsOwnIsListedAndRestorable(): void
    {
        $api = $this->api();
        $noteId = $api->createNote('Single');
        $id = $api->upload('/notes/' . $noteId . '/attachments')['id'];
        $api->request('DELETE', '/attachments/' . $id);

        $trash = $api->request('GET', '/trash');
        $this->assertSame([$id], array_column($trash['attachments'], 'id'));
        $this->assertSame($noteId, $trash['attachments'][0]['note_id']);

        $api->request('POST', '/trash/attachments/' . $id . '/restore');
        $this->assertSame(200, $api->status());
    }

    public function testAnAttachmentCannotBeRestoredWhileItsNoteIsInTheTrash(): void
    {
        $api = $this->api();
        $noteId = $api->createNote('Parent');
        $id = $api->upload('/notes/' . $noteId . '/attachments')['id'];
        $api->request('DELETE', '/notes/' . $noteId);

        $body = $api->request('POST', '/trash/attachments/' . $id . '/restore');

        $this->assertSame(409, $api->status());
        $this->assertSame('notes.parent_note_in_trash', $body['code']);
    }

    public function testTrashingAFolderCarriesNoteAttachmentsAndEmptyingTheTrashDeletesThem(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('Docs');
        $noteId = $api->createNote('Inside', '', $folderId);
        $id = $api->upload('/notes/' . $noteId . '/attachments')['id'];

        $api->request('DELETE', '/folders/' . $folderId);
        $api->request('POST', '/trash/folders/' . $folderId . '/restore');
        $this->assertCount(1, $api->request('GET', '/notes/' . $noteId)['attachments']);

        $api->request('DELETE', '/folders/' . $folderId);
        $api->request('DELETE', '/trash');
        $api->request('GET', '/attachments/' . $id);
        $this->assertSame(404, $api->status());
    }

    public function testAUserCannotUploadToOrDownloadFromAnotherUsersNote(): void
    {
        $client = static::createClient();
        $ownerLogin = new LoginService();
        $ownerLogin->execute($client);
        $owner = new NotesApi($client, $ownerLogin->getAccessToken());
        $noteId = $owner->createNote('Mine');
        $id = $owner->upload('/notes/' . $noteId . '/attachments')['id'];

        $intruderLogin = new LoginService();
        $intruderLogin->execute($client);
        $intruder = new NotesApi($client, $intruderLogin->getAccessToken());

        $intruder->upload('/notes/' . $noteId . '/attachments');
        $this->assertSame(404, $intruder->status());

        $client->request('GET', '/attachments/' . $id . '/content', [], [], $intruder->authServer());
        $this->assertSame(404, $intruder->status());
    }
}
