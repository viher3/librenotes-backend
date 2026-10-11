<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LinksTest extends WebTestCase
{
    private function api(): NotesApi
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        return new NotesApi($client, $login->getAccessToken());
    }

    private function createLink(NotesApi $api, array $data = []): string
    {
        $body = $api->request('POST', '/links', $data + ['title' => 'Symfony', 'url' => 'https://symfony.com']);
        $this->assertSame(201, $api->status(), json_encode($body));

        return $body['id'];
    }

    public function testCreateAndReadALink(): void
    {
        $api = $this->api();
        $id = $this->createLink($api, ['note' => 'Docs', 'tags' => ['Dev', 'PHP']]);

        $link = $api->request('GET', '/links/' . $id);

        $this->assertSame(200, $api->status());
        $this->assertEqualsCanonicalizing(
            ['id', 'title', 'icon', 'url', 'note', 'folder_id', 'parent_note_id', 'path', 'tags', 'created_at', 'updated_at'],
            array_keys($link)
        );
        $this->assertSame('https://symfony.com', $link['url']);
        $this->assertSame('Docs', $link['note']);
        $this->assertSame(['dev', 'php'], $link['tags']);
        $this->assertNull($link['folder_id']);
    }

    public function testTheDetailSaysWhereTheLinkSits(): void
    {
        $api = $this->api();
        $outer = $api->createFolder('Outer');
        $inner = $api->createFolder('Inner', $outer);
        $note = $api->createNote('Project', '', $inner);
        $child = $api->createNote('Task', '', null, ['parent_note_id' => $note]);

        $atRoot = $this->createLink($api);
        $inFolder = $this->createLink($api, ['folder_id' => $inner]);
        $underNote = $this->createLink($api, ['parent_note_id' => $child]);

        $this->assertSame([], $api->request('GET', '/links/' . $atRoot)['path']);
        $this->assertSame(
            [
                ['type' => 'folder', 'id' => $outer, 'title' => 'Outer'],
                ['type' => 'folder', 'id' => $inner, 'title' => 'Inner'],
            ],
            $api->request('GET', '/links/' . $inFolder)['path'],
            'the direct parent is part of the path'
        );
        $this->assertSame(
            [
                ['type' => 'folder', 'id' => $outer, 'title' => 'Outer'],
                ['type' => 'folder', 'id' => $inner, 'title' => 'Inner'],
                ['type' => 'note', 'id' => $note, 'title' => 'Project'],
                ['type' => 'note', 'id' => $child, 'title' => 'Task'],
            ],
            $api->request('GET', '/links/' . $underNote)['path']
        );
        $this->assertArrayNotHasKey('path', $api->request('GET', '/links')['data'][0], 'lists do not carry it');
    }

    public function testOnlyHttpAndHttpsUrlsAreAccepted(): void
    {
        $api = $this->api();

        foreach (['javascript:alert(1)', 'data:text/html;base64,AAAA', 'ftp://example.com', 'example.com', '//example.com', 'https://', 'https://exa mple.com', 'file:///etc/passwd'] as $url) {
            $body = $api->request('POST', '/links', ['title' => 'x', 'url' => $url]);
            $this->assertSame(422, $api->status(), $url);
            $this->assertSame('link.invalid_url', $body['code'], $url);
        }

        foreach (['http://example.com', 'HTTPS://Example.com/a?b=c#d', 'https://example.com:8443/path'] as $url) {
            $api->request('POST', '/links', ['title' => 'x', 'url' => $url]);
            $this->assertSame(201, $api->status(), $url);
        }
    }

    public function testCreateRequiresTitleAndUrl(): void
    {
        $api = $this->api();

        $api->request('POST', '/links', ['url' => 'https://a.co']);
        $this->assertSame(400, $api->status());

        $api->request('POST', '/links', ['title' => 'x']);
        $this->assertSame(400, $api->status());
    }

    public function testUpdateChangesFieldsAndCanClearTheNote(): void
    {
        $api = $this->api();
        $id = $this->createLink($api, ['note' => 'old']);

        $api->request('PATCH', '/links/' . $id, ['title' => 'New', 'url' => 'https://new.example', 'note' => null, 'tags' => ['x']]);
        $this->assertSame(200, $api->status());

        $link = $api->request('GET', '/links/' . $id);
        $this->assertSame('New', $link['title']);
        $this->assertSame('https://new.example', $link['url']);
        $this->assertNull($link['note']);
        $this->assertSame(['x'], $link['tags']);

        $body = $api->request('PATCH', '/links/' . $id, ['url' => 'javascript:alert(1)']);
        $this->assertSame(422, $api->status());
        $this->assertSame('link.invalid_url', $body['code']);
    }

    public function testLinksCanBeMovedBetweenFoldersAndShowInFolderContents(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('Reading');
        $id = $this->createLink($api, ['folder_id' => $folderId]);

        $contents = $api->request('GET', '/folders/contents', null, ['folder_id' => $folderId]);
        $this->assertSame([$id], array_column($contents['links'], 'id'));
        $this->assertSame([], $api->request('GET', '/folders/contents')['links']);

        $api->request('PATCH', '/links/' . $id . '/move', ['folder_id' => null]);
        $this->assertSame(200, $api->status());
        $this->assertSame([$id], array_column($api->request('GET', '/folders/contents')['links'], 'id'));

        $api->request('PATCH', '/links/' . $id . '/move', ['folder_id' => '00000000-0000-4000-8000-000000000000']);
        $this->assertSame(422, $api->status());
    }

    public function testCreatingInAMissingFolderFails(): void
    {
        $api = $this->api();

        $api->request('POST', '/links', ['title' => 'x', 'url' => 'https://a.co', 'folder_id' => '00000000-0000-4000-8000-000000000000']);

        $this->assertSame(422, $api->status());
    }

    public function testListIsPaginatedAndFilteredByFolderAndTag(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('F');
        $a = $this->createLink($api, ['title' => 'a', 'tags' => ['work'], 'folder_id' => $folderId]);
        $b = $this->createLink($api, ['title' => 'b', 'tags' => ['work', 'home']]);
        $c = $this->createLink($api, ['title' => 'c']);

        $ids = fn (array $query) => array_column($api->request('GET', '/links', null, $query)['data'], 'id');

        $this->assertEqualsCanonicalizing([$a, $b], $ids(['tag' => 'Work']));
        $this->assertSame([$a], $ids(['folder_id' => $folderId]));
        $this->assertEqualsCanonicalizing([$b, $c], $ids(['folder_id' => 'root']));
        $this->assertSame([$b], $ids(['tag' => 'home']));

        $page = $api->request('GET', '/links', null, ['size' => 2, 'orderBy' => 'title', 'orderDirection' => 'asc']);
        $this->assertSame(['page' => 1, 'size' => 2, 'total' => 3, 'last_page' => 2], $page['meta']);
        $this->assertSame(['a', 'b'], array_column($page['data'], 'title'));

        $api->request('GET', '/links', null, ['orderBy' => 'url']);
        $this->assertSame(400, $api->status());
    }

    public function testTrashRestoreAndPermanentDeletion(): void
    {
        $api = $this->api();
        $id = $this->createLink($api);

        $api->request('DELETE', '/links/' . $id);
        $this->assertSame(200, $api->status());
        $this->assertSame([], $api->request('GET', '/links')['data']);

        $trash = $api->request('GET', '/trash');
        $this->assertSame([$id], array_column($trash['links'], 'id'));
        $this->assertEqualsCanonicalizing(['id', 'title', 'icon', 'url', 'folder_id', 'deleted_at'], array_keys($trash['links'][0]));

        $api->request('POST', '/trash/links/' . $id . '/restore');
        $this->assertSame(200, $api->status());
        $this->assertSame([$id], array_column($api->request('GET', '/links')['data'], 'id'));

        $body = $api->request('DELETE', '/trash/links/' . $id);
        $this->assertSame(409, $api->status());
        $this->assertSame('notes.not_in_trash', $body['code']);

        $api->request('DELETE', '/links/' . $id);
        $api->request('DELETE', '/trash/links/' . $id);
        $this->assertSame(200, $api->status());
        $api->request('GET', '/links/' . $id);
        $this->assertSame(404, $api->status());
    }

    public function testFolderTrashCarriesItsLinksAndEmptyingTheTrashDeletesThem(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('Temp');
        $id = $this->createLink($api, ['folder_id' => $folderId]);

        $api->request('DELETE', '/folders/' . $folderId);
        $this->assertSame([], $api->request('GET', '/links')['data']);

        $api->request('POST', '/trash/folders/' . $folderId . '/restore');
        $this->assertSame([$id], array_column($api->request('GET', '/links')['data'], 'id'));

        $api->request('DELETE', '/folders/' . $folderId);
        $api->request('DELETE', '/trash');
        $api->request('GET', '/links/' . $id);
        $this->assertSame(404, $api->status());
    }

    public function testALinkCannotBeRestoredIntoATrashedFolder(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('Temp');
        $id = $this->createLink($api, ['folder_id' => $folderId]);
        $api->request('DELETE', '/links/' . $id);
        $api->request('DELETE', '/folders/' . $folderId);

        $body = $api->request('POST', '/trash/links/' . $id . '/restore');

        $this->assertSame(409, $api->status());
        $this->assertSame('notes.parent_folder_in_trash', $body['code']);
    }

    public function testAUserCannotReachAnotherUsersLinks(): void
    {
        $client = static::createClient();
        $ownerLogin = new LoginService();
        $ownerLogin->execute($client);
        $owner = new NotesApi($client, $ownerLogin->getAccessToken());
        $id = $this->createLink($owner);

        $intruderLogin = new LoginService();
        $intruderLogin->execute($client);
        $intruder = new NotesApi($client, $intruderLogin->getAccessToken());

        foreach ([['GET', '/links/' . $id], ['PATCH', '/links/' . $id, ['title' => 'x']], ['DELETE', '/links/' . $id], ['PATCH', '/links/' . $id . '/move', ['folder_id' => null]]] as [$method, $uri, $body]) {
            $intruder->request($method, $uri, $body ?? null);
            $this->assertSame(404, $intruder->status(), "$method $uri");
        }

        $this->assertSame([], $intruder->request('GET', '/links')['data']);
        $this->assertSame([$id], array_column($owner->request('GET', '/links')['data'], 'id'));
    }
}
