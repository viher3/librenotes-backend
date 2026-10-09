<?php

namespace App\Tests\Acceptance\User\Controller;

use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CurrentUserControllerTest extends WebTestCase
{
    private function request(KernelBrowser $client, string $method, string $uri, ?array $body, string $token): array
    {
        $client->request(
            $method,
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            $body === null ? null : json_encode($body)
        );

        return json_decode($client->getResponse()->getContent(), true) ?? [];
    }

    public function testMeReturnsTheAuthenticatedUserInSnakeCase(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        $me = $this->request($client, 'GET', '/me', null, $login->getAccessToken());

        $this->assertResponseIsSuccessful();
        $this->assertEqualsCanonicalizing(
            ['id', 'email', 'username', 'locale', 'roles', 'active', 'email_validated', 'created_at', 'last_login_at'],
            array_keys($me)
        );
        $this->assertSame($login->getUserId(), $me['id']);
        $this->assertSame($login->getEmail(), $me['email']);
        $this->assertSame('en', $me['locale']);
        $this->assertTrue($me['active']);
        $this->assertIsArray($me['roles']);
    }

    public function testMeRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPatchMeUpdatesLocaleAndUsername(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        $this->request($client, 'PATCH', '/me', ['locale' => 'es', 'username' => 'nuevo_nombre'], $login->getAccessToken());
        $this->assertResponseIsSuccessful();

        $me = $this->request($client, 'GET', '/me', null, $login->getAccessToken());
        $this->assertSame('es', $me['locale']);
        $this->assertSame('nuevo_nombre', $me['username']);
    }

    public function testPatchMeRejectsUnsupportedLocale(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        $this->request($client, 'PATCH', '/me', ['locale' => 'xx'], $login->getAccessToken());

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPatchMeIgnoresFieldsThatAreNotSelfServiceEditable(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);
        $originalEmail = $login->getEmail();

        $this->request($client, 'PATCH', '/me', ['email' => 'hacker@example.com', 'active' => false], $login->getAccessToken());
        $this->assertResponseIsSuccessful();

        $me = $this->request($client, 'GET', '/me', null, $login->getAccessToken());
        $this->assertSame($originalEmail, $me['email']);
        $this->assertTrue($me['active']);
    }

    public function testUpdateUserEndpointAcceptsLocale(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        $this->request($client, 'PATCH', '/users/' . $login->getUserId(), ['locale' => 'es'], $login->getAccessToken());

        $this->assertResponseIsSuccessful();
    }
}
