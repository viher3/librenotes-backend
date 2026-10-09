<?php

namespace App\Tests\Acceptance\Notes\Service;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Thin JSON helper over the Notes HTTP API for acceptance tests, bound to one authenticated user.
 */
class NotesApi
{
    public function __construct(
        private KernelBrowser $client,
        private string $accessToken
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function request(string $method, string $uri, ?array $body = null, array $parameters = []): array
    {
        $this->client->request(
            $method,
            $uri,
            $parameters,
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken],
            $body === null ? null : json_encode($body)
        );

        $content = $this->client->getResponse()->getContent();

        return $content ? (json_decode($content, true) ?? []) : [];
    }

    public function createFolder(string $name, ?string $parentFolderId = null): string
    {
        return $this->request('POST', '/folders', ['name' => $name, 'parent_folder_id' => $parentFolderId])['id'];
    }

    public function createNote(string $title, string $content = '', ?string $folderId = null, array $extra = []): string
    {
        return $this->request('POST', '/notes', [
            'title' => $title,
            'content' => $content,
            'folder_id' => $folderId,
            ...$extra,
        ])['id'];
    }

    public function upload(string $uri, string $fileName = 'hello.txt', string $contents = 'hello', string $mime = 'text/plain'): array
    {
        $path = tempnam(sys_get_temp_dir(), 'att');
        file_put_contents($path, $contents);

        $this->client->request(
            'POST',
            $uri,
            [],
            ['file' => new UploadedFile($path, $fileName, $mime, null, true)],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken]
        );

        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function authServer(): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->accessToken];
    }

    public function uploadFromPath(string $uri, string $path, string $fileName, string $mime = 'application/octet-stream'): array
    {
        $this->client->request(
            'POST',
            $uri,
            [],
            ['file' => new UploadedFile($path, $fileName, $mime, null, true)],
            $this->authServer()
        );

        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }

    public function status(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }

    public function client(): KernelBrowser
    {
        return $this->client;
    }
}
