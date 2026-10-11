<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotesFlowTest extends WebTestCase
{
    private function api(): NotesApi
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        return new NotesApi($client, $login->getAccessToken());
    }

    public function testNoteDetailUsesSnakeCase(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('Work');
        $noteId = $api->createNote('Plan', '# Hello', $folderId);

        $note = $api->request('GET', '/notes/' . $noteId);

        $this->assertSame(200, $api->status());
        $this->assertEqualsCanonicalizing(
            ['id', 'title', 'icon', 'content', 'folder_id', 'parent_note_id', 'path', 'child_count', 'pinned', 'tags', 'created_at', 'updated_at', 'attachments'],
            array_keys($note)
        );
        $this->assertSame('# Hello', $note['content']);
        $this->assertSame($folderId, $note['folder_id']);
    }

    public function testFolderContentsUsesSnakeCase(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('Parent');
        $subfolderId = $api->createFolder('Child', $folderId);
        $api->createNote('In parent', '', $folderId);
        $api->upload('/folders/' . $folderId . '/attachments', 'a.txt');

        $contents = $api->request('GET', '/folders/contents', null, ['folder_id' => $folderId]);

        $this->assertSame(200, $api->status());
        $this->assertSame(['folder', 'subfolders', 'notes', 'links', 'attachments'], array_keys($contents));
        $this->assertSame($folderId, $contents['folder']['id']);
        $this->assertArrayHasKey('parent_folder_id', $contents['folder']);
        $this->assertSame($subfolderId, $contents['subfolders'][0]['id']);
        $this->assertArrayHasKey('created_at', $contents['subfolders'][0]);
        $this->assertEqualsCanonicalizing(['id', 'title', 'icon', 'pinned', 'tags', 'child_count', 'created_at', 'updated_at'], array_keys($contents['notes'][0]));
        $this->assertEqualsCanonicalizing(
            ['id', 'file_name', 'mime_type', 'size_bytes', 'created_at'],
            array_keys($contents['attachments'][0])
        );
    }

    public function testAttachmentDetailUsesSnakeCase(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('Files');
        $id = $api->upload('/folders/' . $folderId . '/attachments', 'a.txt', 'abc')['id'];

        $attachment = $api->request('GET', '/attachments/' . $id);

        $this->assertEqualsCanonicalizing(
            ['id', 'folder_id', 'note_id', 'file_name', 'mime_type', 'size_bytes', 'created_at'],
            array_keys($attachment)
        );
        $this->assertSame('a.txt', $attachment['file_name']);
        $this->assertSame(3, $attachment['size_bytes']);
    }

    public function testTrashListUsesSnakeCaseAndRestoreWorks(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('Old');
        $noteId = $api->createNote('Gone', '', $folderId);
        $attachmentId = $api->upload('/folders/' . $folderId . '/attachments')['id'];
        $api->request('DELETE', '/folders/' . $folderId);

        $trash = $api->request('GET', '/trash');

        $this->assertSame(['folders', 'notes', 'links', 'attachments'], array_keys($trash));
        $this->assertSame($folderId, $trash['folders'][0]['id']);
        $this->assertArrayHasKey('parent_folder_id', $trash['folders'][0]);
        $this->assertArrayHasKey('deleted_at', $trash['folders'][0]);
        $this->assertEqualsCanonicalizing(['id', 'title', 'icon', 'folder_id', 'deleted_at'], array_keys($trash['notes'][0]));
        $this->assertEqualsCanonicalizing(
            ['id', 'file_name', 'folder_id', 'note_id', 'deleted_at'],
            array_keys($trash['attachments'][0])
        );

        $api->request('POST', '/trash/folders/' . $folderId . '/restore');
        $this->assertSame(200, $api->status());
        $api->request('GET', '/notes/' . $noteId);
        $this->assertSame(200, $api->status());
        $api->request('GET', '/attachments/' . $attachmentId);
        $this->assertSame(200, $api->status());
    }

    public function testNoteTitleAndContentCanBeUpdated(): void
    {
        $api = $this->api();
        $id = $api->createNote('Old title', 'old');
        $before = $api->request('GET', '/notes/' . $id)['updated_at'];
        sleep(1);

        $api->request('PATCH', '/notes/' . $id, ['title' => 'New title', 'content' => '# New']);
        $this->assertSame(200, $api->status());

        $note = $api->request('GET', '/notes/' . $id);
        $this->assertSame('New title', $note['title']);
        $this->assertSame('# New', $note['content']);
        $this->assertGreaterThan($before, $note['updated_at']);
    }

    public function testUpdatingAnUnknownFieldIsRejected(): void
    {
        $api = $this->api();
        $id = $api->createNote('Title');

        $body = $api->request('PATCH', '/notes/' . $id, ['ownerId' => '00000000-0000-4000-8000-000000000000']);

        $this->assertSame(422, $api->status());
        $this->assertSame('invalid_update_fields', $body['code']);
    }

    public function testCreateNoteValidatesInput(): void
    {
        $api = $this->api();

        $api->request('POST', '/notes', []);
        $this->assertSame(400, $api->status());

        $api->request('POST', '/notes', ['title' => '']);
        $this->assertSame(400, $api->status());
    }
}
