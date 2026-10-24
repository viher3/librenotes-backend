<?php

namespace App\Tests\Acceptance\User\Controller;

use App\Tests\Acceptance\Security\Service\LoginService;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChangeCurrentUserPasswordControllerTest extends WebTestCase
{
    private const string NEW_PASSWORD = 'a-brand-new-password';

    private function login(KernelBrowser $client): LoginService
    {
        $login = new LoginService();
        $login->execute($client);

        return $login;
    }

    private function changePassword(KernelBrowser $client, ?string $token, array $body): array
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $client->request('PATCH', '/me/password', [], [], $server, json_encode($body));

        return json_decode($client->getResponse()->getContent() ?: '[]', true) ?? [];
    }

    private function loginWith(KernelBrowser $client, string $email, string $password): void
    {
        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password])
        );
    }

    private function currentPassword(LoginService $login): string
    {
        $property = new \ReflectionProperty($login, 'userFixture');

        return $property->getValue($login)->getPassword();
    }

    public function testChangesThePasswordAndRevokesTheExistingSessions(): void
    {
        $client = static::createClient();
        $login = $this->login($client);
        $current = $this->currentPassword($login);

        $this->changePassword($client, $login->getAccessToken(), [
            'current_password' => $current,
            'new_password' => self::NEW_PASSWORD,
        ]);
        $this->assertResponseStatusCodeSame(204);

        // The access token issued before the change is no longer accepted...
        $client->request('GET', '/me', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $login->getAccessToken()]);
        $this->assertResponseStatusCodeSame(401);

        // ...nor can the refresh token renew it.
        $client->request(
            'PUT',
            '/token-renew',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $login->getRefreshToken()])
        );
        $this->assertResponseStatusCodeSame(401);

        // The old password stops working and the new one logs in.
        $this->loginWith($client, $login->getEmail(), $current);
        $this->assertResponseStatusCodeSame(401);

        $this->loginWith($client, $login->getEmail(), self::NEW_PASSWORD);
        $this->assertResponseIsSuccessful();
        $newToken = json_decode($client->getResponse()->getContent(), true)['accessToken'];

        $client->request('GET', '/me', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $newToken]);
        $this->assertResponseIsSuccessful();
    }

    public function testRejectsAWrongCurrentPassword(): void
    {
        $client = static::createClient();
        $login = $this->login($client);

        $response = $this->changePassword($client, $login->getAccessToken(), [
            'current_password' => 'not-the-password',
            'new_password' => self::NEW_PASSWORD,
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('user.invalid_password', $response['code']);

        // Nothing changed: the original password still logs in.
        $this->loginWith($client, $login->getEmail(), $this->currentPassword($login));
        $this->assertResponseIsSuccessful();
    }

    public function testRejectsTheSamePassword(): void
    {
        $client = static::createClient();
        $login = $this->login($client);
        $current = $this->currentPassword($login);

        $response = $this->changePassword($client, $login->getAccessToken(), [
            'current_password' => $current,
            'new_password' => $current,
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('user.password_unchanged', $response['code']);
    }

    public function testLocksAfterThreeFailedAttempts(): void
    {
        $client = static::createClient();
        $login = $this->login($client);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->changePassword($client, $login->getAccessToken(), [
                'current_password' => 'wrong-password-' . $attempt,
                'new_password' => self::NEW_PASSWORD,
            ]);
            $this->assertResponseStatusCodeSame(422);
        }

        // Even the right password is refused while locked.
        $response = $this->changePassword($client, $login->getAccessToken(), [
            'current_password' => $this->currentPassword($login),
            'new_password' => self::NEW_PASSWORD,
        ]);

        $this->assertResponseStatusCodeSame(429);
        $this->assertSame('user.change_password_rate_limited', $response['code']);
        $this->assertGreaterThan(0, $response['params']['retry_after']);
        $this->assertSame(
            (string) $response['params']['retry_after'],
            $client->getResponse()->headers->get('Retry-After')
        );
    }

    public function testASuccessfulChangeDoesNotLeaveAttemptsPending(): void
    {
        $client = static::createClient();
        $login = $this->login($client);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $this->changePassword($client, $login->getAccessToken(), [
                'current_password' => 'wrong-password',
                'new_password' => self::NEW_PASSWORD,
            ]);
            $this->assertResponseStatusCodeSame(422);
        }

        $this->changePassword($client, $login->getAccessToken(), [
            'current_password' => $this->currentPassword($login),
            'new_password' => self::NEW_PASSWORD,
        ]);
        $this->assertResponseStatusCodeSame(204);
    }

    #[DataProvider('invalidBodies')]
    public function testRejectsInvalidBodies(array $body): void
    {
        $client = static::createClient();
        $login = $this->login($client);

        $this->changePassword($client, $login->getAccessToken(), $body);

        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidBodies(): array
    {
        return [
            'missing new password' => [['current_password' => 'whatever-123']],
            'missing current password' => [['new_password' => self::NEW_PASSWORD]],
            'empty current password' => [['current_password' => '', 'new_password' => self::NEW_PASSWORD]],
            'new password too short' => [['current_password' => 'whatever-123', 'new_password' => 'short']],
            'new password too long' => [['current_password' => 'whatever-123', 'new_password' => str_repeat('a', 45)]],
            'wrong type' => [['current_password' => 123, 'new_password' => self::NEW_PASSWORD]],
            'unknown field' => [['current_password' => 'whatever-123', 'new_password' => self::NEW_PASSWORD, 'user_id' => 'x']],
        ];
    }

    public function testRequiresAuthentication(): void
    {
        $client = static::createClient();

        $this->changePassword($client, null, [
            'current_password' => 'whatever-123',
            'new_password' => self::NEW_PASSWORD,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testTheOldPerUserRouteNoLongerExists(): void
    {
        $client = static::createClient();
        $login = $this->login($client);

        $client->request(
            'PATCH',
            '/users/' . $login->getUserId() . '/change-password',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $login->getAccessToken()],
            json_encode(['currentPassword' => 'x', 'newPassword' => self::NEW_PASSWORD])
        );

        $this->assertResponseStatusCodeSame(404);
    }
}
