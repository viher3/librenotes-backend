<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TagsTest extends WebTestCase
{
    private function api(): NotesApi
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        return new NotesApi($client, $login->getAccessToken());
    }

    public function testTagsListCountsNotesAndLinksOrderedByUsage(): void
    {
        $api = $this->api();
        $api->createNote('a', '', null, ['tags' => ['work', 'home']]);
        $api->createNote('b', '', null, ['tags' => ['work']]);
        $api->request('POST', '/links', ['title' => 'c', 'url' => 'https://a.co', 'tags' => ['work', 'dev']]);
        $trashed = $api->createNote('d', '', null, ['tags' => ['trashed-only', 'home']]);
        $api->request('DELETE', '/notes/' . $trashed);

        $tags = $api->request('GET', '/tags');

        $this->assertSame(200, $api->status());
        $this->assertSame(
            [['name' => 'work', 'count' => 3], ['name' => 'dev', 'count' => 1], ['name' => 'home', 'count' => 1]],
            $tags['data']
        );
    }

    public function testTagsAreScopedToTheUserAndEmptyByDefault(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);
        $api = new NotesApi($client, $login->getAccessToken());
        $this->assertSame([], $api->request('GET', '/tags')['data']);

        $otherLogin = new LoginService();
        $otherLogin->execute($client);
        $other = new NotesApi($client, $otherLogin->getAccessToken());
        $other->createNote('x', '', null, ['tags' => ['private']]);

        $this->assertSame([], $api->request('GET', '/tags')['data']);
    }
}
