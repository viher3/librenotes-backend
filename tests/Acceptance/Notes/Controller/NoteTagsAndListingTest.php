<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NoteTagsAndListingTest extends WebTestCase
{
    private function api(): NotesApi
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        return new NotesApi($client, $login->getAccessToken());
    }

    public function testNotesStartUnpinnedAndWithoutTags(): void
    {
        $api = $this->api();
        $id = $api->createNote('Plain');

        $note = $api->request('GET', '/notes/' . $id);

        $this->assertFalse($note['pinned']);
        $this->assertSame([], $note['tags']);
    }

    public function testTagsAreNormalizedDeduplicatedAndPersisted(): void
    {
        $api = $this->api();
        $id = $api->createNote('Tagged', '', null, ['tags' => ['Work', ' work ', 'Ideas'], 'pinned' => true]);

        $note = $api->request('GET', '/notes/' . $id);

        $this->assertSame(['work', 'ideas'], $note['tags']);
        $this->assertTrue($note['pinned']);
    }

    public function testTagsAndPinnedCanBeUpdatedAndReplaced(): void
    {
        $api = $this->api();
        $id = $api->createNote('Edit me', '', null, ['tags' => ['a', 'b']]);

        $api->request('PATCH', '/notes/' . $id, ['tags' => ['c'], 'pinned' => true]);
        $this->assertSame(200, $api->status());

        $note = $api->request('GET', '/notes/' . $id);
        $this->assertSame(['c'], $note['tags']);
        $this->assertTrue($note['pinned']);

        $api->request('PATCH', '/notes/' . $id, ['tags' => []]);
        $this->assertSame([], $api->request('GET', '/notes/' . $id)['tags']);
    }

    public function testInvalidTagsAreRejected(): void
    {
        $api = $this->api();

        $body = $api->request('POST', '/notes', ['title' => 'x', 'tags' => ['']]);
        $this->assertSame(422, $api->status());
        $this->assertSame('tag.invalid', $body['code']);

        $api->request('POST', '/notes', ['title' => 'x', 'tags' => [str_repeat('a', 51)]]);
        $this->assertSame(422, $api->status());

        $api->request('POST', '/notes', ['title' => 'x', 'tags' => array_map(fn ($i) => "t$i", range(1, 21))]);
        $this->assertSame(422, $api->status());

        $api->request('POST', '/notes', ['title' => 'x', 'tags' => 'work']);
        $this->assertSame(400, $api->status());
    }

    public function testListIsPaginatedAndOrdered(): void
    {
        $api = $this->api();
        foreach (['b', 'a', 'c'] as $title) {
            $api->createNote($title);
        }

        $page1 = $api->request('GET', '/notes', null, ['size' => 2, 'orderBy' => 'title', 'orderDirection' => 'asc']);

        $this->assertSame(200, $api->status());
        $this->assertSame(['page' => 1, 'size' => 2, 'total' => 3, 'last_page' => 2], $page1['meta']);
        $this->assertSame(['a', 'b'], array_column($page1['data'], 'title'));
        $this->assertEqualsCanonicalizing(
            ['id', 'title', 'folder_id', 'pinned', 'tags', 'created_at', 'updated_at'],
            array_keys($page1['data'][0])
        );

        $page2 = $api->request('GET', '/notes', null, ['size' => 2, 'page' => 2, 'orderBy' => 'title', 'orderDirection' => 'asc']);
        $this->assertSame(['c'], array_column($page2['data'], 'title'));
    }

    public function testListFiltersByFolderRootTagAndPinned(): void
    {
        $api = $this->api();
        $folderId = $api->createFolder('F');
        $inFolder = $api->createNote('in folder', '', $folderId, ['tags' => ['work']]);
        $atRoot = $api->createNote('at root', '', null, ['tags' => ['work', 'home'], 'pinned' => true]);
        $other = $api->createNote('other');

        $ids = fn (array $query) => array_column($api->request('GET', '/notes', null, $query)['data'], 'id');

        $this->assertSame([$inFolder], $ids(['folder_id' => $folderId]));
        $this->assertEqualsCanonicalizing([$atRoot, $other], $ids(['folder_id' => 'root']));
        $this->assertEqualsCanonicalizing([$inFolder, $atRoot], $ids(['tag' => 'work']));
        $this->assertEqualsCanonicalizing([$inFolder, $atRoot], $ids(['tag' => ' WORK ']), 'the filter is normalized like stored tags');
        $this->assertSame([$atRoot], $ids(['tag' => 'home']));
        $this->assertSame([$atRoot], $ids(['pinned' => 'true']));
        $this->assertSame([$atRoot], $ids(['tag' => 'work', 'pinned' => '1', 'folder_id' => 'root']));
        $this->assertSame([], $ids(['tag' => 'missing']));
    }

    public function testListExcludesTrashedNotesAndOtherUsersNotes(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);
        $api = new NotesApi($client, $login->getAccessToken());
        $kept = $api->createNote('kept');
        $trashed = $api->createNote('trashed');
        $api->request('DELETE', '/notes/' . $trashed);

        $otherLogin = new LoginService();
        $otherLogin->execute($client);
        $other = new NotesApi($client, $otherLogin->getAccessToken());
        $other->createNote('not mine', '', null, ['tags' => ['work']]);

        $this->assertSame([$kept], array_column($api->request('GET', '/notes')['data'], 'id'));
        $this->assertSame([], $api->request('GET', '/notes', null, ['tag' => 'work'])['data']);
    }

    public function testListRejectsInvalidParameters(): void
    {
        $api = $this->api();

        foreach ([['orderBy' => 'content'], ['orderDirection' => 'sideways'], ['page' => '0'], ['size' => 'x'], ['folder_id' => 'nope'], ['pinned' => 'maybe']] as $query) {
            $api->request('GET', '/notes', null, $query);
            $this->assertSame(400, $api->status(), json_encode($query));
        }
    }

    public function testListCapsThePageSize(): void
    {
        $api = $this->api();
        $api->createNote('one');

        $this->assertSame(100, $api->request('GET', '/notes', null, ['size' => 1000])['meta']['size']);
    }

    public function testFolderContentsExposeTagsAndPinned(): void
    {
        $api = $this->api();
        $api->createNote('root note', '', null, ['tags' => ['x'], 'pinned' => true]);

        $note = $api->request('GET', '/folders/contents')['notes'][0];

        $this->assertSame(['x'], $note['tags']);
        $this->assertTrue($note['pinned']);
    }
}
