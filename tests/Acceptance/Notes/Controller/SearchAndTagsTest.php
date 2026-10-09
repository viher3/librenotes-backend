<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SearchAndTagsTest extends WebTestCase
{
    private function api(): NotesApi
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        return new NotesApi($client, $login->getAccessToken());
    }

    private function search(NotesApi $api, string $q, array $extra = []): array
    {
        return $api->request('GET', '/search', null, ['q' => $q] + $extra);
    }

    private function ids(array $result): array
    {
        return array_column($result['data'], 'id');
    }

    public function testFindsNotesByTitleAndContent(): void
    {
        $api = $this->api();
        $byTitle = $api->createNote('Quarterly roadmap', 'nothing here');
        $byContent = $api->createNote('Meeting', 'We discussed the roadmap for next year');
        $api->createNote('Unrelated', 'groceries');

        $result = $this->search($api, 'roadmap');

        $this->assertSame(200, $api->status());
        $this->assertEqualsCanonicalizing([$byTitle, $byContent], $this->ids($result));
        $this->assertSame(['page' => 1, 'size' => 20, 'total' => 2, 'last_page' => 1], $result['meta']);
        $this->assertSame($byTitle, $result['data'][0]['id'], 'title matches rank first');
    }

    public function testItemShapeAndSnippet(): void
    {
        $api = $this->api();
        $api->createNote('Notes', str_repeat('lorem ipsum ', 20) . "special\n\nkeyword " . str_repeat('dolor ', 40), null, ['tags' => ['Work']]);

        $item = $this->search($api, 'keyword')['data'][0];

        $this->assertEqualsCanonicalizing(
            ['type', 'id', 'title', 'snippet', 'folder_id', 'tags', 'updated_at', 'url'],
            array_keys($item)
        );
        $this->assertSame('note', $item['type']);
        $this->assertSame(['work'], $item['tags']);
        $this->assertNull($item['url']);
        $this->assertStringContainsString('special keyword', $item['snippet']);
        $this->assertStringStartsWith('…', $item['snippet']);
        $this->assertStringEndsWith('…', $item['snippet']);
        $this->assertStringNotContainsString("\n", $item['snippet']);
        $this->assertLessThanOrEqual(170, mb_strlen($item['snippet']));
    }

    public function testFindsLinksByTitleNoteAndUrl(): void
    {
        $api = $this->api();
        $byTitle = $api->request('POST', '/links', ['title' => 'Symfony docs', 'url' => 'https://example.org/a'])['id'];
        $byNote = $api->request('POST', '/links', ['title' => 'x', 'url' => 'https://example.org/b', 'note' => 'about symfony internals'])['id'];
        $byUrl = $api->request('POST', '/links', ['title' => 'y', 'url' => 'https://symfony.com/z'])['id'];
        $api->request('POST', '/links', ['title' => 'other', 'url' => 'https://example.org/c']);

        $result = $this->search($api, 'symfony');

        $this->assertEqualsCanonicalizing([$byTitle, $byNote, $byUrl], $this->ids($result));
        $link = array_values(array_filter($result['data'], fn ($i) => $i['id'] === $byUrl))[0];
        $this->assertSame('link', $link['type']);
        $this->assertSame('https://symfony.com/z', $link['url']);
    }

    public function testAllTermsMustMatchAndMatchingIsCaseAndAccentInsensitive(): void
    {
        $api = $this->api();
        $both = $api->createNote('Reunión de planificación', 'presupuesto anual');
        $api->createNote('Reunión', 'otra cosa');

        $this->assertSame([$both], $this->ids($this->search($api, 'REUNION presupuesto')));
        $this->assertSame([$both], $this->ids($this->search($api, 'planificacion')));
    }

    public function testShortWordsAndStopwordsAreFound(): void
    {
        $api = $this->api();
        $id = $api->createNote('To do', 'buy the milk');

        $this->assertSame([$id], $this->ids($this->search($api, 'to')));
        $this->assertSame([$id], $this->ids($this->search($api, 'the milk')));
    }

    public function testWildcardCharactersAreTreatedLiterally(): void
    {
        $api = $this->api();
        $percent = $api->createNote('Discount 50% off');
        $api->createNote('Discount 500 off');
        $underscore = $api->createNote('snake_case name');
        $api->createNote('snakeXcase name');

        $this->assertSame([$percent], $this->ids($this->search($api, '50%')));
        $this->assertSame([$underscore], $this->ids($this->search($api, 'snake_case')));
        $this->assertSame([], $this->ids($this->search($api, '%%')));
    }

    public function testFiltersByTypeAndTag(): void
    {
        $api = $this->api();
        $note = $api->createNote('alpha note', '', null, ['tags' => ['t1']]);
        $link = $api->request('POST', '/links', ['title' => 'alpha link', 'url' => 'https://a.co', 'tags' => ['t1']])['id'];
        $api->createNote('alpha untagged');

        $this->assertEqualsCanonicalizing([$note, $link], $this->ids($this->search($api, 'alpha', ['tag' => 'T1'])));
        $this->assertSame([$note], $this->ids($this->search($api, 'alpha', ['type' => 'note', 'tag' => 't1'])));
        $this->assertSame([$link], $this->ids($this->search($api, 'alpha', ['type' => 'link'])));
        $this->assertCount(3, $this->ids($this->search($api, 'alpha', ['type' => 'note,link'])));
    }

    public function testExcludesTrashedItemsAndOtherUsersData(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);
        $api = new NotesApi($client, $login->getAccessToken());
        $kept = $api->createNote('findme kept');
        $trashed = $api->createNote('findme trashed');
        $api->request('DELETE', '/notes/' . $trashed);

        $otherLogin = new LoginService();
        $otherLogin->execute($client);
        $other = new NotesApi($client, $otherLogin->getAccessToken());
        $other->createNote('findme secret');

        $this->assertSame([$kept], $this->ids($this->search($api, 'findme')));
    }

    public function testPaginates(): void
    {
        $api = $this->api();
        foreach (range(1, 5) as $i) {
            $api->createNote("page item $i");
        }

        $page2 = $this->search($api, 'page item', ['size' => 2, 'page' => 2]);

        $this->assertSame(['page' => 2, 'size' => 2, 'total' => 5, 'last_page' => 3], $page2['meta']);
        $this->assertCount(2, $page2['data']);
    }

    public function testValidatesParameters(): void
    {
        $api = $this->api();

        $api->request('GET', '/search');
        $this->assertSame(400, $api->status());

        $body = $this->search($api, 'a');
        $this->assertSame(422, $api->status());
        $this->assertSame('search.query_too_short', $body['code']);

        $this->search($api, '   ');
        $this->assertSame(422, $api->status());

        $this->search($api, 'ok', ['type' => 'folder']);
        $this->assertSame(400, $api->status());

        $this->search($api, 'ok', ['page' => '0']);
        $this->assertSame(400, $api->status());
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
