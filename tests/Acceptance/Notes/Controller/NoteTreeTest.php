<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Notes (and links) can live under another note, forming a tree next to the folders.
 */
class NoteTreeTest extends WebTestCase
{
    private const MISSING = '00000000-0000-4000-8000-000000000000';

    private function api(): NotesApi
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        return new NotesApi($client, $login->getAccessToken());
    }

    private function noteUnder(NotesApi $api, string $title, string $parentId, array $extra = []): string
    {
        return $api->createNote($title, '', null, ['parent_note_id' => $parentId] + $extra);
    }

    private function linkUnder(NotesApi $api, string $title, string $parentId): string
    {
        $body = $api->request('POST', '/links', ['title' => $title, 'url' => 'https://a.co', 'parent_note_id' => $parentId]);
        $this->assertSame(201, $api->status(), json_encode($body));

        return $body['id'];
    }

    private function ids(array $items): array
    {
        return array_column($items, 'id');
    }

    public function testANoteCanBeCreatedUnderAnotherNote(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Project');

        $child = $this->noteUnder($api, 'Task', $parent);

        $note = $api->request('GET', '/notes/' . $child);
        $this->assertSame($parent, $note['parent_note_id']);
        $this->assertNull($note['folder_id']);
        $this->assertSame([['type' => 'note', 'id' => $parent, 'title' => 'Project']], $note['path']);
        $this->assertSame(0, $note['child_count']);
        $this->assertSame(1, $api->request('GET', '/notes/' . $parent)['child_count']);
        $this->assertSame([], $api->request('GET', '/notes/' . $parent)['path']);
    }

    public function testChildrenAreListedWithTheirOwnChildCount(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Project');
        $withKids = $this->noteUnder($api, 'B with kids', $parent);
        $leaf = $this->noteUnder($api, 'A leaf', $parent);
        $this->noteUnder($api, 'grandchild', $withKids);
        $this->linkUnder($api, 'grandlink', $withKids);
        $link = $this->linkUnder($api, 'Docs', $parent);

        $children = $api->request('GET', '/notes/' . $parent . '/children');

        $this->assertSame(200, $api->status());
        $this->assertSame(['notes', 'links'], array_keys($children));
        $this->assertSame([$leaf, $withKids], $this->ids($children['notes']), 'ordered by title');
        $this->assertSame([0, 2], array_column($children['notes'], 'child_count'));
        $this->assertEqualsCanonicalizing(
            ['id', 'title', 'icon', 'pinned', 'tags', 'child_count', 'created_at', 'updated_at'],
            array_keys($children['notes'][0])
        );
        $this->assertSame([$link], $this->ids($children['links']));
        $this->assertSame('https://a.co', $children['links'][0]['url']);
    }

    public function testNestedItemsDoNotShowUpAtTheRoot(): void
    {
        $api = $this->api();
        $top = $api->createNote('Top');
        $nested = $this->noteUnder($api, 'Nested', $top);
        $nestedLink = $this->linkUnder($api, 'Nested link', $top);
        $rootLink = $api->request('POST', '/links', ['title' => 'Root link', 'url' => 'https://a.co'])['id'];

        $root = $api->request('GET', '/folders/contents');
        $this->assertSame([$top], $this->ids($root['notes']));
        $this->assertSame([$rootLink], $this->ids($root['links']));
        $this->assertSame(2, $root['notes'][0]['child_count']);

        $this->assertSame([$top], $this->ids($api->request('GET', '/notes', null, ['folder_id' => 'root'])['data']));
        $this->assertSame([$rootLink], $this->ids($api->request('GET', '/links', null, ['folder_id' => 'root'])['data']));
        $this->assertEqualsCanonicalizing([$top, $nested], $this->ids($api->request('GET', '/notes')['data']), 'unfiltered lists every note');
        $this->assertNotContains($nestedLink, $this->ids($api->request('GET', '/links', null, ['folder_id' => 'root'])['data']));
    }

    public function testListsCanBeFilteredByParentNote(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Parent');
        $other = $api->createNote('Other');
        $child = $this->noteUnder($api, 'Child', $parent);
        $this->noteUnder($api, 'Other child', $other);
        $link = $this->linkUnder($api, 'Link', $parent);

        $notes = $api->request('GET', '/notes', null, ['parent_note_id' => $parent]);
        $this->assertSame([$child], $this->ids($notes['data']));
        $this->assertSame($parent, $notes['data'][0]['parent_note_id']);
        $this->assertSame([$link], $this->ids($api->request('GET', '/links', null, ['parent_note_id' => $parent])['data']));

        $api->request('GET', '/notes', null, ['parent_note_id' => 'nope']);
        $this->assertSame(400, $api->status());
    }

    public function testAnItemLivesInAFolderOrUnderANoteNeverBoth(): void
    {
        $api = $this->api();
        $folder = $api->createFolder('F');
        $parent = $api->createNote('P');

        $body = $api->request('POST', '/notes', ['title' => 'x', 'folder_id' => $folder, 'parent_note_id' => $parent]);
        $this->assertSame(422, $api->status());
        $this->assertSame('notes.invalid_location', $body['code']);

        $body = $api->request('POST', '/links', ['title' => 'x', 'url' => 'https://a.co', 'folder_id' => $folder, 'parent_note_id' => $parent]);
        $this->assertSame(422, $api->status());
        $this->assertSame('notes.invalid_location', $body['code']);

        $note = $api->createNote('movable');
        $api->request('PATCH', '/notes/' . $note . '/move', ['folder_id' => $folder, 'parent_note_id' => $parent]);
        $this->assertSame(422, $api->status());
        $this->assertSame([], $api->request('GET', '/notes/' . $note . '/children')['notes']);
    }

    public function testTheParentMustExistBeOwnedAndNotInTheTrash(): void
    {
        $client = static::createClient();
        $ownerLogin = new LoginService();
        $ownerLogin->execute($client);
        $owner = new NotesApi($client, $ownerLogin->getAccessToken());
        $foreign = $owner->createNote('Private');
        $trashed = $owner->createNote('Trashed');
        $owner->request('DELETE', '/notes/' . $trashed);

        $api = (function () use ($client) {
            $login = new LoginService();
            $login->execute($client);

            return new NotesApi($client, $login->getAccessToken());
        })();

        foreach ([self::MISSING, $foreign] as $parent) {
            $body = $api->request('POST', '/notes', ['title' => 'x', 'parent_note_id' => $parent]);
            $this->assertSame(422, $api->status(), $parent);
            $this->assertSame('note.parent_not_found', $body['code']);
        }
        $api->request('POST', '/links', ['title' => 'x', 'url' => 'https://a.co', 'parent_note_id' => $foreign]);
        $this->assertSame(422, $api->status());

        $this->assertSame(422, $this->statusOfCreatingUnder($owner, $trashed));
    }

    private function statusOfCreatingUnder(NotesApi $api, string $parent): int
    {
        $api->request('POST', '/notes', ['title' => 'x', 'parent_note_id' => $parent]);

        return $api->status();
    }

    public function testNotesMoveAcrossFoldersParentsAndTheRoot(): void
    {
        $api = $this->api();
        $folder = $api->createFolder('F');
        $a = $api->createNote('A');
        $b = $api->createNote('B', '', $folder);
        $link = $api->request('POST', '/links', ['title' => 'L', 'url' => 'https://a.co'])['id'];

        $api->request('PATCH', '/notes/' . $b . '/move', ['parent_note_id' => $a]);
        $this->assertSame(200, $api->status());
        $moved = $api->request('GET', '/notes/' . $b);
        $this->assertSame($a, $moved['parent_note_id']);
        $this->assertNull($moved['folder_id']);
        $this->assertSame([], $api->request('GET', '/folders/contents', null, ['folder_id' => $folder])['notes']);

        $api->request('PATCH', '/links/' . $link . '/move', ['parent_note_id' => $a]);
        $this->assertSame(200, $api->status());
        $this->assertSame($a, $api->request('GET', '/links/' . $link)['parent_note_id']);

        $api->request('PATCH', '/notes/' . $b . '/move', ['folder_id' => $folder]);
        $moved = $api->request('GET', '/notes/' . $b);
        $this->assertSame($folder, $moved['folder_id']);
        $this->assertNull($moved['parent_note_id']);

        $api->request('PATCH', '/notes/' . $b . '/move', []);
        $moved = $api->request('GET', '/notes/' . $b);
        $this->assertNull($moved['folder_id']);
        $this->assertNull($moved['parent_note_id']);
        $this->assertContains($b, $this->ids($api->request('GET', '/folders/contents')['notes']));

        $api->request('PATCH', '/links/' . $link . '/move', ['parent_note_id' => self::MISSING]);
        $this->assertSame(422, $api->status());
    }

    public function testANoteCannotBeMovedUnderItselfOrItsDescendants(): void
    {
        $api = $this->api();
        $a = $api->createNote('A');
        $b = $this->noteUnder($api, 'B', $a);
        $c = $this->noteUnder($api, 'C', $b);

        foreach ([$a, $b, $c] as $target) {
            $body = $api->request('PATCH', '/notes/' . $a . '/move', ['parent_note_id' => $target]);
            $this->assertSame(422, $api->status(), "A under $target");
            $this->assertSame('note.cycle_detected', $body['code']);
        }
        $body = $api->request('PATCH', '/notes/' . $b . '/move', ['parent_note_id' => $c]);
        $this->assertSame(422, $api->status());
        $this->assertSame('note.cycle_detected', $body['code']);

        $this->assertNull($api->request('GET', '/notes/' . $a)['parent_note_id']);

        // Moving a subtree somewhere that is not inside it is fine.
        $d = $api->createNote('D');
        $api->request('PATCH', '/notes/' . $b . '/move', ['parent_note_id' => $d]);
        $this->assertSame(200, $api->status());
        $this->assertSame(
            [['type' => 'note', 'id' => $d, 'title' => 'D'], ['type' => 'note', 'id' => $b, 'title' => 'B']],
            $api->request('GET', '/notes/' . $c)['path'],
            'the moved subtree keeps its shape under the new parent'
        );
    }

    public function testThePathListsFoldersAndNotesFromTheRoot(): void
    {
        $api = $this->api();
        $outer = $api->createFolder('Outer');
        $inner = $api->createFolder('Inner', $outer);
        $top = $api->createNote('Top', '', $inner);
        $middle = $this->noteUnder($api, 'Middle', $top);
        $leaf = $this->noteUnder($api, 'Leaf', $middle);

        $this->assertSame(
            [
                ['type' => 'folder', 'id' => $outer, 'title' => 'Outer'],
                ['type' => 'folder', 'id' => $inner, 'title' => 'Inner'],
                ['type' => 'note', 'id' => $top, 'title' => 'Top'],
                ['type' => 'note', 'id' => $middle, 'title' => 'Middle'],
            ],
            $api->request('GET', '/notes/' . $leaf)['path']
        );
        $this->assertSame(
            [['type' => 'folder', 'id' => $outer, 'title' => 'Outer'], ['type' => 'folder', 'id' => $inner, 'title' => 'Inner']],
            $api->request('GET', '/notes/' . $top)['path']
        );

        $contents = $api->request('GET', '/folders/contents', null, ['folder_id' => $inner]);
        $this->assertSame([['type' => 'folder', 'id' => $outer, 'title' => 'Outer']], $contents['folder']['path']);
        $this->assertSame([], $api->request('GET', '/folders/contents', null, ['folder_id' => $outer])['folder']['path']);
    }

    public function testDeepTreesWork(): void
    {
        $api = $this->api();
        $id = $api->createNote('level 0');
        foreach (range(1, 25) as $level) {
            $id = $this->noteUnder($api, "level $level", $id);
        }

        $note = $api->request('GET', '/notes/' . $id);

        $this->assertSame(200, $api->status());
        $this->assertCount(25, $note['path']);
        $this->assertSame('level 0', $note['path'][0]['title']);
    }

    public function testTrashingANoteTakesItsWholeSubtreeAndRestoringBringsItBack(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Parent');
        $child = $this->noteUnder($api, 'Child', $parent);
        $grandchild = $this->noteUnder($api, 'Grandchild', $child);
        $link = $this->linkUnder($api, 'Link', $child);
        $attachment = $api->upload('/notes/' . $grandchild . '/attachments', 'a.txt', 'abc')['id'];

        $api->request('DELETE', '/notes/' . $parent);
        $this->assertSame(200, $api->status());

        $this->assertSame([], $api->request('GET', '/notes')['data']);
        $this->assertSame([], $api->request('GET', '/links')['data']);
        $trash = $api->request('GET', '/trash');
        $this->assertSame([$parent], $this->ids($trash['notes']), 'descendants travel with their parent');
        $this->assertSame([], $trash['links']);
        $this->assertSame([], $trash['attachments']);
        $api->client()->request('GET', '/attachments/' . $attachment . '/content', [], [], $api->authServer());
        $this->assertSame(404, $api->status());
        $api->request('GET', '/notes/' . $parent . '/children');
        $this->assertSame(404, $api->status());

        $api->request('POST', '/trash/notes/' . $parent . '/restore');
        $this->assertSame(200, $api->status());
        $this->assertEqualsCanonicalizing([$parent, $child, $grandchild], $this->ids($api->request('GET', '/notes')['data']));
        $this->assertSame([$link], $this->ids($api->request('GET', '/links')['data']));
        $this->assertCount(1, $api->request('GET', '/notes/' . $grandchild)['attachments']);
    }

    public function testAChildCannotBeRestoredWhileItsParentNoteIsInTheTrash(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Parent');
        $child = $this->noteUnder($api, 'Child', $parent);
        $link = $this->linkUnder($api, 'Link', $parent);
        $api->request('DELETE', '/notes/' . $child);
        $api->request('DELETE', '/links/' . $link);
        $api->request('DELETE', '/notes/' . $parent);

        $body = $api->request('POST', '/trash/notes/' . $child . '/restore');
        $this->assertSame(409, $api->status());
        $this->assertSame('notes.parent_note_in_trash', $body['code']);
        $body = $api->request('POST', '/trash/links/' . $link . '/restore');
        $this->assertSame(409, $api->status());
        $this->assertSame('notes.parent_note_in_trash', $body['code']);
    }

    public function testAChildTrashedOnItsOwnIsListedAndRestorable(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Parent');
        $child = $this->noteUnder($api, 'Child', $parent);
        $api->request('DELETE', '/notes/' . $child);

        $this->assertSame([$child], $this->ids($api->request('GET', '/trash')['notes']));
        $this->assertSame(0, $api->request('GET', '/notes/' . $parent)['child_count']);
        $this->assertSame([], $api->request('GET', '/notes/' . $parent . '/children')['notes']);

        $api->request('POST', '/trash/notes/' . $child . '/restore');
        $this->assertSame(200, $api->status());
        $this->assertSame([$child], $this->ids($api->request('GET', '/notes/' . $parent . '/children')['notes']));
    }

    public function testDeletingANoteForGoodDeletesItsSubtreeAndFiles(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Parent');
        $child = $this->noteUnder($api, 'Child', $parent);
        $link = $this->linkUnder($api, 'Link', $child);
        $attachment = $api->upload('/notes/' . $child . '/attachments')['id'];
        $api->request('DELETE', '/notes/' . $parent);

        $api->request('DELETE', '/trash/notes/' . $parent);
        $this->assertSame(200, $api->status());

        foreach (['/notes/' . $parent, '/notes/' . $child, '/links/' . $link, '/attachments/' . $attachment] as $uri) {
            $api->request('GET', $uri);
            $this->assertSame(404, $api->status(), $uri);
        }
    }

    public function testEmptyingTheTrashCopesWithNestedItems(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Parent');
        $child = $this->noteUnder($api, 'Child', $parent);
        $this->linkUnder($api, 'Link', $child);
        $api->upload('/notes/' . $child . '/attachments');
        $solo = $api->createNote('Solo');
        $this->noteUnder($api, 'Solo child', $solo);
        $api->request('DELETE', '/notes/' . $parent);
        $api->request('DELETE', '/notes/' . $solo);

        $api->request('DELETE', '/trash');

        $this->assertSame(200, $api->status());
        $this->assertSame(['folders' => [], 'notes' => [], 'links' => [], 'attachments' => []], $api->request('GET', '/trash'));
        $this->assertSame([], $api->request('GET', '/notes')['data']);
    }

    public function testTrashingAFolderTakesTheNoteTreesInsideIt(): void
    {
        $api = $this->api();
        $folder = $api->createFolder('Docs');
        $top = $api->createNote('Top', '', $folder);
        $child = $this->noteUnder($api, 'Child', $top);
        $link = $this->linkUnder($api, 'Link', $child);

        $api->request('DELETE', '/folders/' . $folder);
        $this->assertSame([], $api->request('GET', '/notes')['data']);
        $this->assertSame([], $api->request('GET', '/links')['data']);

        $api->request('POST', '/trash/folders/' . $folder . '/restore');
        $this->assertEqualsCanonicalizing([$top, $child], $this->ids($api->request('GET', '/notes')['data']));
        $this->assertSame([$link], $this->ids($api->request('GET', '/links')['data']));

        $api->request('DELETE', '/folders/' . $folder);
        $api->request('DELETE', '/trash/folders/' . $folder);
        $this->assertSame(200, $api->status());
        foreach (['/notes/' . $top, '/notes/' . $child, '/links/' . $link] as $uri) {
            $api->request('GET', $uri);
            $this->assertSame(404, $api->status(), $uri);
        }
    }

    public function testNestedNotesAreFoundBySearchAndCountedInTags(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Parent');
        $child = $this->noteUnder($api, 'Needle in a haystack', $parent, ['tags' => ['deep']]);

        $this->assertSame([$child], $this->ids($api->request('GET', '/search', null, ['q' => 'needle'])['data']));
        $this->assertSame([['name' => 'deep', 'count' => 1]], $api->request('GET', '/tags')['data']);
    }

    public function testAUserCannotSeeOrUseAnotherUsersTree(): void
    {
        $client = static::createClient();
        $ownerLogin = new LoginService();
        $ownerLogin->execute($client);
        $owner = new NotesApi($client, $ownerLogin->getAccessToken());
        $parent = $owner->createNote('Mine');
        $child = $this->noteUnder($owner, 'Child', $parent);

        $intruderLogin = new LoginService();
        $intruderLogin->execute($client);
        $intruder = new NotesApi($client, $intruderLogin->getAccessToken());

        $intruder->request('GET', '/notes/' . $parent . '/children');
        $this->assertSame(404, $intruder->status());
        $intruder->request('PATCH', '/notes/' . $child . '/move', ['parent_note_id' => null]);
        $this->assertSame(404, $intruder->status());
        $own = $intruder->createNote('Theirs');
        $intruder->request('PATCH', '/notes/' . $own . '/move', ['parent_note_id' => $parent]);
        $this->assertSame(422, $intruder->status());
        $this->assertSame([], $intruder->request('GET', '/notes', null, ['parent_note_id' => $parent])['data']);
        $this->assertSame($parent, $owner->request('GET', '/notes/' . $child)['parent_note_id']);
    }
}
