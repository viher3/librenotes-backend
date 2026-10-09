<?php

namespace App\Tests\Acceptance\Notes\Controller;

use App\Tests\Acceptance\Notes\Service\NotesApi;
use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NotesOwnershipTest extends WebTestCase
{
    public function testAUserCannotReachAnotherUsersData(): void
    {
        $client = static::createClient();
        $ownerLogin = new LoginService();
        $ownerLogin->execute($client);
        $owner = new NotesApi($client, $ownerLogin->getAccessToken());

        $folderId = $owner->createFolder('Private');
        $noteId = $owner->createNote('Secret', 'x', $folderId);
        $attachmentId = $owner->upload('/folders/' . $folderId . '/attachments')['id'];

        $intruderLogin = new LoginService();
        $intruderLogin->execute($client);
        $intruder = new NotesApi($client, $intruderLogin->getAccessToken());

        foreach ([
            ['GET', '/notes/' . $noteId],
            ['PATCH', '/notes/' . $noteId, ['title' => 'hacked']],
            ['DELETE', '/notes/' . $noteId],
            ['GET', '/attachments/' . $attachmentId],
            ['PATCH', '/folders/' . $folderId, ['name' => 'hacked']],
            ['DELETE', '/folders/' . $folderId],
        ] as [$method, $uri, $body]) {
            $intruder->request($method, $uri, $body ?? null);
            $this->assertSame(404, $intruder->status(), "$method $uri must look like not found");
        }

        $this->assertSame(404, $this->statusOf($intruder, 'POST', '/folders/' . $folderId . '/attachments'));

        $contents = $intruder->request('GET', '/folders/contents');
        $this->assertSame([], $contents['notes']);
        $this->assertSame([], $contents['subfolders']);

        $this->assertSame('Secret', $owner->request('GET', '/notes/' . $noteId)['title']);
    }

    private function statusOf(NotesApi $api, string $method, string $uri): int
    {
        $api->upload($uri);

        return $api->status();
    }
}
