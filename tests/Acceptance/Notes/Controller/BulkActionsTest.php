<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Moving and trashing many folders, notes, links and files in one request: all or nothing.
 */
class BulkActionsTest extends WebTestCase
{
    private const MISSING = '00000000-0000-4000-8000-000000000000';

    private function api(): NotesApi
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        return new NotesApi($client, $login->getAccessToken());
    }

    private function link(NotesApi $api, string $title, array $placement = []): string
    {
        $body = $api->request('POST', '/links', ['title' => $title, 'url' => 'https://a.co'] + $placement);
        $this->assertSame(201, $api->status(), json_encode($body));

        return $body['id'];
    }

    /** @return array{type: string, id: string} */
    private function item(string $type, string $id): array
    {
        return ['type' => $type, 'id' => $id];
    }

    private function ids(array $items): array
    {
        return array_column($items, 'id');
    }

    private function contents(NotesApi $api, ?string $folderId = null): array
    {
        return $api->request('GET', '/folders/contents', null, $folderId === null ? [] : ['folder_id' => $folderId]);
    }

    public function testMixedItemsMoveToAFolder(): void
    {
        $api = $this->api();
        $source = $api->createFolder('Source');
        $target = $api->createFolder('Target');
        $folder = $api->createFolder('Sub', $source);
        $note = $api->createNote('Note', '', $source);
        $link = $this->link($api, 'Link', ['folder_id' => $source]);
        $file = $api->upload('/folders/' . $source . '/attachments')['id'];

        $body = $api->request('POST', '/bulk/move', [
            'items' => [
                $this->item('folder', $folder),
                $this->item('note', $note),
                $this->item('link', $link),
                $this->item('attachment', $file),
            ],
            'folder_id' => $target,
        ]);

        $this->assertSame(200, $api->status(), json_encode($body));
        $this->assertSame(['data' => ['count' => 4]], $body);
        $moved = $this->contents($api, $target);
        $this->assertSame([$folder], $this->ids($moved['subfolders']));
        $this->assertSame([$note], $this->ids($moved['notes']));
        $this->assertSame([$link], $this->ids($moved['links']));
        $this->assertSame([$file], $this->ids($moved['attachments']));
        $left = $this->contents($api, $source);
        $this->assertSame([], $left['subfolders'] + $left['notes'] + $left['links'] + $left['attachments']);
    }

    public function testNotesLinksAndFilesMoveUnderANote(): void
    {
        $api = $this->api();
        $folder = $api->createFolder('Docs');
        $parent = $api->createNote('Project');
        $note = $api->createNote('Task', '', $folder);
        $link = $this->link($api, 'Link', ['folder_id' => $folder]);
        $file = $api->upload('/folders/' . $folder . '/attachments')['id'];

        $api->request('POST', '/bulk/move', [
            'items' => [$this->item('note', $note), $this->item('link', $link), $this->item('attachment', $file)],
            'parent_note_id' => $parent,
        ]);

        $this->assertSame(200, $api->status());
        $children = $api->request('GET', '/notes/' . $parent . '/children');
        $this->assertSame([$note], $this->ids($children['notes']));
        $this->assertSame([$link], $this->ids($children['links']));
        $attachment = $api->request('GET', '/attachments/' . $file);
        $this->assertSame($parent, $attachment['note_id']);
        $this->assertNull($attachment['folder_id']);
    }

    public function testFoldersCannotGoUnderANoteAndNothingMoves(): void
    {
        $api = $this->api();
        $parent = $api->createNote('Project');
        $folder = $api->createFolder('Docs');
        $note = $api->createNote('Task');

        $body = $api->request('POST', '/bulk/move', [
            'items' => [$this->item('note', $note), $this->item('folder', $folder)],
            'parent_note_id' => $parent,
        ]);

        $this->assertSame(422, $api->status());
        $this->assertSame('notes.invalid_location', $body['code']);
        $this->assertNull($api->request('GET', '/notes/' . $note)['parent_note_id']);
    }

    public function testItemsMoveToTheTopLevelButFilesCannot(): void
    {
        $api = $this->api();
        $folder = $api->createFolder('Docs');
        $inner = $api->createFolder('Inner', $folder);
        $note = $api->createNote('Task', '', $folder);
        $file = $api->upload('/folders/' . $folder . '/attachments')['id'];

        $body = $api->request('POST', '/bulk/move', [
            'items' => [$this->item('note', $note), $this->item('attachment', $file)],
        ]);
        $this->assertSame(422, $api->status());
        $this->assertSame('attachment.invalid_location', $body['code']);
        $this->assertSame($folder, $api->request('GET', '/notes/' . $note)['folder_id']);

        $api->request('POST', '/bulk/move', [
            'items' => [$this->item('note', $note), $this->item('folder', $inner)],
        ]);
        $this->assertSame(200, $api->status());
        $root = $this->contents($api);
        $this->assertContains($note, $this->ids($root['notes']));
        $this->assertContains($inner, $this->ids($root['subfolders']));
    }

    public function testMovingAFolderOrNoteKeepsItsFilesWithIt(): void
    {
        $api = $this->api();
        $target = $api->createFolder('Target');
        $folder = $api->createFolder('Docs');
        $inFolder = $api->upload('/folders/' . $folder . '/attachments')['id'];
        $note = $api->createNote('Spec');
        $inNote = $api->upload('/notes/' . $note . '/attachments')['id'];

        $api->request('POST', '/bulk/move', [
            'items' => [$this->item('folder', $folder), $this->item('note', $note)],
            'folder_id' => $target,
        ]);

        $this->assertSame(200, $api->status());
        $this->assertSame($folder, $api->request('GET', '/attachments/' . $inFolder)['folder_id']);
        $this->assertSame([$inFolder], $this->ids($this->contents($api, $folder)['attachments']));
        $this->assertSame($note, $api->request('GET', '/attachments/' . $inNote)['note_id']);
        $detail = $api->request('GET', '/notes/' . $note);
        $this->assertSame($target, $detail['folder_id']);
        $this->assertSame([$inNote], $this->ids($detail['attachments']));
    }

    public function testNothingMovesWhenAnItemWouldEndUpInsideItself(): void
    {
        $api = $this->api();
        $other = $api->createNote('Other');
        $parent = $api->createNote('Parent');
        $child = $api->createNote('Child', '', null, ['parent_note_id' => $parent]);
        $folder = $api->createFolder('A');
        $inner = $api->createFolder('B', $folder);

        $body = $api->request('POST', '/bulk/move', [
            'items' => [$this->item('note', $other), $this->item('note', $parent)],
            'parent_note_id' => $child,
        ]);
        $this->assertSame(422, $api->status());
        $this->assertSame('note.cycle_detected', $body['code']);
        $this->assertNull($api->request('GET', '/notes/' . $other)['parent_note_id']);

        $body = $api->request('POST', '/bulk/move', [
            'items' => [$this->item('note', $other), $this->item('folder', $folder)],
            'folder_id' => $inner,
        ]);
        $this->assertSame(422, $api->status());
        $this->assertSame('folder.cycle_detected', $body['code']);
        $this->assertNull($api->request('GET', '/notes/' . $other)['folder_id']);
    }

    public function testSelectedItemsInsideOtherSelectedItemsTravelWithThem(): void
    {
        $api = $this->api();
        $target = $api->createFolder('Target');
        $folder = $api->createFolder('Docs');
        $inFolder = $api->createNote('In folder', '', $folder);
        $parent = $api->createNote('Parent', '', $folder);
        $child = $api->createNote('Child', '', null, ['parent_note_id' => $parent]);
        $file = $api->upload('/notes/' . $parent . '/attachments')['id'];

        $body = $api->request('POST', '/bulk/move', [
            'items' => [
                $this->item('note', $child),
                $this->item('folder', $folder),
                $this->item('note', $inFolder),
                $this->item('attachment', $file),
                $this->item('folder', $folder),
            ],
            'folder_id' => $target,
        ]);

        $this->assertSame(200, $api->status());
        $this->assertSame(['data' => ['count' => 1]], $body);
        $this->assertSame([$folder], $this->ids($this->contents($api, $target)['subfolders']));
        $this->assertSame($folder, $api->request('GET', '/notes/' . $inFolder)['folder_id']);
        $this->assertSame($parent, $api->request('GET', '/notes/' . $child)['parent_note_id']);
        $this->assertSame($parent, $api->request('GET', '/attachments/' . $file)['note_id']);
    }

    public function testAMissingItemOrDestinationChangesNothing(): void
    {
        $api = $this->api();
        $target = $api->createFolder('Target');
        $note = $api->createNote('Task');
        $trashed = $api->createNote('Gone');
        $api->request('DELETE', '/notes/' . $trashed);

        $body = $api->request('POST', '/bulk/move', [
            'items' => [$this->item('note', $note), $this->item('link', self::MISSING)],
            'folder_id' => $target,
        ]);
        $this->assertSame(404, $api->status());
        $this->assertSame('link.not_found', $body['code']);
        $this->assertNull($api->request('GET', '/notes/' . $note)['folder_id']);

        $body = $api->request('POST', '/bulk/move', [
            'items' => [$this->item('note', $note), $this->item('note', $trashed)],
            'folder_id' => $target,
        ]);
        $this->assertSame(404, $api->status());
        $this->assertSame('note.not_found', $body['code']);

        $body = $api->request('POST', '/bulk/move', ['items' => [$this->item('note', $note)], 'folder_id' => self::MISSING]);
        $this->assertSame(422, $api->status());
        $this->assertSame('folder.not_found', $body['code']);

        $body = $api->request('POST', '/bulk/move', ['items' => [$this->item('note', $note)], 'parent_note_id' => self::MISSING]);
        $this->assertSame(422, $api->status());
        $this->assertSame('note.parent_not_found', $body['code']);
        $this->assertNull($api->request('GET', '/notes/' . $note)['folder_id']);
    }

    public function testTheRequestIsValidated(): void
    {
        $api = $this->api();
        $note = $api->createNote('Task');

        $api->request('POST', '/bulk/move', ['items' => []]);
        $this->assertSame(400, $api->status());

        $api->request('POST', '/bulk/trash', ['items' => [['type' => 'tag', 'id' => $note]]]);
        $this->assertSame(400, $api->status());

        $api->request('POST', '/bulk/trash', ['items' => array_fill(0, 101, $this->item('note', $note))]);
        $this->assertSame(400, $api->status());

        $api->request('POST', '/bulk/trash', ['items' => array_fill(0, 100, $this->item('note', $note))]);
        $this->assertSame(200, $api->status(), 'repeated items collapse, so 100 entries are fine');
    }

    public function testAUserCannotMoveOrTrashAnotherUsersItems(): void
    {
        $client = static::createClient();
        $ownerLogin = new LoginService();
        $ownerLogin->execute($client);
        $owner = new NotesApi($client, $ownerLogin->getAccessToken());
        $folder = $owner->createFolder('Private');
        $note = $owner->createNote('Secret', '', $folder);
        $file = $owner->upload('/folders/' . $folder . '/attachments')['id'];

        $intruderLogin = new LoginService();
        $intruderLogin->execute($client);
        $intruder = new NotesApi($client, $intruderLogin->getAccessToken());
        $mine = $intruder->createFolder('Mine');
        $theirs = $intruder->createNote('Theirs');

        foreach (['/bulk/move', '/bulk/trash'] as $uri) {
            foreach ([$this->item('note', $note), $this->item('folder', $folder), $this->item('attachment', $file)] as $foreign) {
                $intruder->request('POST', $uri, ['items' => [$this->item('note', $theirs), $foreign], 'folder_id' => $mine]);
                $this->assertSame(404, $intruder->status(), "$uri with a foreign {$foreign['type']}");
            }
        }
        $intruder->request('POST', '/bulk/move', ['items' => [$this->item('note', $theirs)], 'folder_id' => $folder]);
        $this->assertSame(422, $intruder->status(), 'a foreign destination is not a valid destination');

        $this->assertNull($intruder->request('GET', '/notes/' . $theirs)['folder_id']);
        $this->assertSame($folder, $owner->request('GET', '/notes/' . $note)['folder_id']);
        $this->assertSame([$file], $this->ids($this->contents($owner, $folder)['attachments']));
    }

    public function testBulkTrashSendsEverythingToTheTrashAndItCanBeRestored(): void
    {
        $api = $this->api();
        $folder = $api->createFolder('Docs');
        $api->createNote('In folder', '', $folder);
        $parent = $api->createNote('Parent');
        $child = $api->createNote('Child', '', null, ['parent_note_id' => $parent]);
        $noteFile = $api->upload('/notes/' . $parent . '/attachments')['id'];
        $link = $this->link($api, 'Link');
        $loose = $api->createFolder('Loose');
        $looseFile = $api->upload('/folders/' . $loose . '/attachments')['id'];

        $body = $api->request('POST', '/bulk/trash', [
            'items' => [
                $this->item('folder', $folder),
                $this->item('note', $parent),
                $this->item('note', $child),
                $this->item('link', $link),
                $this->item('attachment', $looseFile),
            ],
        ]);

        $this->assertSame(200, $api->status(), json_encode($body));
        $this->assertSame(['data' => ['count' => 4]], $body);
        $root = $this->contents($api);
        $this->assertNotContains($folder, $this->ids($root['subfolders']));
        $this->assertNotContains($parent, $this->ids($root['notes']));
        $this->assertNotContains($link, $this->ids($root['links']));
        $this->assertSame([], $this->contents($api, $loose)['attachments']);

        $trash = $api->request('GET', '/trash');
        $this->assertContains($folder, $this->ids($trash['folders']));
        $this->assertContains($parent, $this->ids($trash['notes']));
        $this->assertNotContains($child, $this->ids($trash['notes']), 'it is listed with its parent');
        $this->assertContains($link, $this->ids($trash['links']));
        $this->assertContains($looseFile, $this->ids($trash['attachments']));
        $this->assertNotContains($noteFile, $this->ids($trash['attachments']), 'it travels with its note');

        $api->request('POST', '/trash/notes/' . $parent . '/restore');
        $this->assertSame(200, $api->status());
        $restored = $api->request('GET', '/notes/' . $parent);
        $this->assertSame([$noteFile], $this->ids($restored['attachments']));
        $this->assertSame([$child], $this->ids($api->request('GET', '/notes/' . $parent . '/children')['notes']));
        $api->request('POST', '/trash/attachments/' . $looseFile . '/restore');
        $this->assertSame(200, $api->status());
    }

    public function testATrashedItemCannotBeTrashedAgain(): void
    {
        $api = $this->api();
        $note = $api->createNote('Task');
        $api->request('DELETE', '/notes/' . $note);

        $api->request('POST', '/bulk/trash', ['items' => [$this->item('note', $note)]]);

        $this->assertSame(404, $api->status());
    }
}
