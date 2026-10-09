<?php

namespace App\Tests\Acceptance\User\Controller;

use App\Tests\Acceptance\Security\Service\LoginService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Regression tests for the request schemas of PATCH /users/{id} and PATCH /users/{id}/change-password:
 * both declared a bogus "required" entry that the validator used to ignore.
 */
class UpdateUserControllerTest extends WebTestCase
{
    private function patch($client, string $uri, array $body, string $token): array
    {
        $client->request(
            'PATCH',
            $uri,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode($body)
        );

        return json_decode($client->getResponse()->getContent(), true) ?? [];
    }

    public function testUpdateUserDoesNotRequireAPassword(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);

        $this->patch($client, '/users/' . $login->getUserId(), ['username' => 'renamed_user'], $login->getAccessToken());

        $this->assertResponseIsSuccessful();
    }

    public function testChangePasswordRequiresBothPasswords(): void
    {
        $client = static::createClient();
        $login = new LoginService();
        $login->execute($client);
        $uri = '/users/' . $login->getUserId() . '/change-password';

        $errors = $this->patch($client, $uri, [], $login->getAccessToken())['errors'];

        $this->assertResponseStatusCodeSame(400);
        $this->assertContains('[currentPassword] The property currentPassword is required', $errors);
        $this->assertContains('[newPassword] The property newPassword is required', $errors);
    }
}
