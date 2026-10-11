<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IconsTest extends WebTestCase
{
    private function api(): NotesApi
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        return new NotesApi($client, $login->getAccessToken());
    }

    public function testItemsHaveNoIconByDefault(): void
    {
        $api = $this->api();
        $folder = $api->createFolder('Work');
        $note = $api->createNote('Plan');
        $link = $api->request('POST', '/links', ['title' => 'Docs', 'url' => 'https://example.com'])['id'];

        $root = $api->request('GET', '/folders/contents');
        $this->assertNull($root['subfolders'][0]['icon']);
        $this->assertNull($root['notes'][0]['icon']);
        $this->assertNull($root['links'][0]['icon']);
        $this->assertNull($api->request('GET', '/folders/contents', null, ['folder_id' => $folder])['folder']['icon']);
        $this->assertNull($api->request('GET', '/notes/' . $note)['icon']);
        $this->assertNull($api->request('GET', '/links/' . $link)['icon']);
    }

    public function testIconsAreSetOnCreationAndChangedAndClearedOnUpdate(): void
    {
        $api = $this->api();

        $folder = $api->request('POST', '/folders', ['name' => 'Work', 'icon' => '🗂️'])['id'];
        $note = $api->createNote('Plan', '', null, ['icon' => '🚀']);
        $link = $api->request('POST', '/links', ['title' => 'Docs', 'url' => 'https://example.com', 'icon' => '📚'])['id'];

        $this->assertSame('🗂️', $api->request('GET', '/folders/contents', null, ['folder_id' => $folder])['folder']['icon']);
        $this->assertSame('🚀', $api->request('GET', '/notes/' . $note)['icon']);
        $this->assertSame('📚', $api->request('GET', '/links/' . $link)['icon']);

        $api->request('PATCH', '/folders/' . $folder, ['icon' => '🇪🇸']);
        $this->assertSame(200, $api->status());
        $api->request('PATCH', '/notes/' . $note, ['icon' => '👩‍💻']);
        $this->assertSame(200, $api->status());
        $api->request('PATCH', '/links/' . $link, ['icon' => '1️⃣']);
        $this->assertSame(200, $api->status());

        $this->assertSame('🇪🇸', $api->request('GET', '/folders/contents', null, ['folder_id' => $folder])['folder']['icon']);
        $this->assertSame('👩‍💻', $api->request('GET', '/notes/' . $note)['icon']);
        $this->assertSame('1️⃣', $api->request('GET', '/links/' . $link)['icon']);

        $api->request('PATCH', '/folders/' . $folder, ['icon' => null]);
        $api->request('PATCH', '/notes/' . $note, ['icon' => null]);
        $api->request('PATCH', '/links/' . $link, ['icon' => null]);

        $this->assertNull($api->request('GET', '/folders/contents', null, ['folder_id' => $folder])['folder']['icon']);
        $this->assertNull($api->request('GET', '/notes/' . $note)['icon']);
        $this->assertNull($api->request('GET', '/links/' . $link)['icon']);
    }

    public function testRenamingAFolderKeepsItsIconAndSettingTheIconKeepsItsName(): void
    {
        $api = $this->api();
        $folder = $api->request('POST', '/folders', ['name' => 'Work', 'icon' => '🗂️'])['id'];

        $api->request('PATCH', '/folders/' . $folder, ['name' => 'Work 2']);
        $detail = $api->request('GET', '/folders/contents', null, ['folder_id' => $folder])['folder'];
        $this->assertSame('Work 2', $detail['name']);
        $this->assertSame('🗂️', $detail['icon']);

        $api->request('PATCH', '/folders/' . $folder, ['icon' => '⭐']);
        $detail = $api->request('GET', '/folders/contents', null, ['folder_id' => $folder])['folder'];
        $this->assertSame('Work 2', $detail['name']);
        $this->assertSame('⭐', $detail['icon']);
    }

    public function testIconsAreListedInNotesLinksChildrenSearchAndTrash(): void
    {
        $api = $this->api();
        $folder = $api->request('POST', '/folders', ['name' => 'Gone', 'icon' => '🗑️'])['id'];
        $parent = $api->createNote('Parent', 'unicorn');
        $child = $api->createNote('Child unicorn', '', null, ['parent_note_id' => $parent, 'icon' => '🦄']);
        $link = $api->request('POST', '/links', ['title' => 'Unicorn site', 'url' => 'https://example.com', 'icon' => '🌐', 'parent_note_id' => $parent])['id'];

        $notes = array_column($api->request('GET', '/notes')['data'], 'icon', 'id');
        $this->assertSame('🦄', $notes[$child]);
        $this->assertNull($notes[$parent]);
        $this->assertSame('🌐', $api->request('GET', '/links')['data'][0]['icon']);

        $children = $api->request('GET', '/notes/' . $parent . '/children');
        $this->assertSame('🦄', $children['notes'][0]['icon']);
        $this->assertSame('🌐', $children['links'][0]['icon']);

        $results = $api->request('GET', '/search', null, ['q' => 'unicorn'])['data'];
        $byId = array_column($results['notes']['items'], 'icon', 'id');
        $this->assertSame('🦄', $byId[$child]);
        $this->assertNull($byId[$parent]);

        $api->request('DELETE', '/folders/' . $folder);
        $api->request('DELETE', '/links/' . $link);
        $trash = $api->request('GET', '/trash');
        $this->assertSame('🗑️', $trash['folders'][0]['icon']);
        $this->assertSame('🌐', $trash['links'][0]['icon']);
    }

    public function testOnlyASingleEmojiIsAccepted(): void
    {
        $api = $this->api();
        $note = $api->createNote('Plan');
        $folder = $api->createFolder('Work');
        $link = $api->request('POST', '/links', ['title' => 'Docs', 'url' => 'https://example.com'])['id'];

        foreach (['a', 'abc', '🚀🚀', '<b>', ' ', '1', '🚀 '] as $icon) {
            $body = $api->request('PATCH', '/notes/' . $note, ['icon' => $icon]);
            $this->assertSame(422, $api->status(), json_encode($icon));
            $this->assertSame('icon.invalid', $body['code'], json_encode($icon));

            $api->request('PATCH', '/folders/' . $folder, ['icon' => $icon]);
            $this->assertSame(422, $api->status(), json_encode($icon));

            $api->request('PATCH', '/links/' . $link, ['icon' => $icon]);
            $this->assertSame(422, $api->status(), json_encode($icon));

            $api->request('POST', '/notes', ['title' => 'x', 'icon' => $icon]);
            $this->assertSame(422, $api->status(), json_encode($icon));
        }

        $api->request('PATCH', '/notes/' . $note, ['icon' => str_repeat('🚀', 40)]);
        $this->assertSame(400, $api->status(), 'longer than 32 characters is a validation error');

        $this->assertNull($api->request('GET', '/notes/' . $note)['icon'], 'nothing was stored');
    }
}
