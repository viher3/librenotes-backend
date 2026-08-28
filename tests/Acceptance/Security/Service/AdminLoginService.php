<?php

namespace App\Tests\Acceptance\Security\Service;

use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\Tests\Acceptance\User\Service\UserFixture;
use App\User\Domain\Model\UserRoleAssignment;
use App\User\Domain\Repository\UserRoleAssignmentRepository;
use App\User\Domain\Repository\UserRoleRepository;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserRoleAssignmentId;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Same as LoginService, but the user is additionally granted ROLE_ADMIN before
 * logging in — for exercising endpoints gated behind it (e.g. POST /users). There
 * is no HTTP path to grant that role (see PromoteUserToAdminCommand), so this
 * assigns it directly through the repository, the same way that console command does.
 */
class AdminLoginService
{
    private const string ADMIN_ROLE_NAME = 'ROLE_ADMIN';

    private UserFixture $userFixture;
    private string $accessToken;
    private string $refreshToken;

    public function execute(KernelBrowser $client): KernelBrowser
    {
        $this->userFixture = new UserFixture();
        $this->userFixture->create($client);

        $container = $client->getContainer();
        $userRoleRepository = $container->get(UserRoleRepository::class);
        $userRoleAssignmentRepository = $container->get(UserRoleAssignmentRepository::class);
        $dateTimeProvider = $container->get(DateTimeProvider::class);

        $role = $userRoleRepository->findByName(self::ADMIN_ROLE_NAME);

        if ($role === null) {
            throw new RuntimeException(sprintf(
                '"%s" is not seeded — run migrations on the test database (make tests/db/reset).',
                self::ADMIN_ROLE_NAME
            ));
        }

        $userRoleAssignmentRepository->save(new UserRoleAssignment(
            id: new UserRoleAssignmentId(Uuid::uuid4()->toString()),
            userId: new UserId($this->userFixture->getId()),
            userRoleId: $role->getId(),
            assignedAt: new DateTimeValueObject($dateTimeProvider->now())
        ));

        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $this->userFixture->getEmail(),
                'password' => $this->userFixture->getPassword()
            ])
        );

        $loginData = json_decode($client->getResponse()->getContent(), true);
        $this->accessToken = $loginData['accessToken'];
        $this->refreshToken = $loginData['refreshToken'];

        return $client;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function getUserId(): string
    {
        return $this->userFixture->getId();
    }

    public function getEmail(): string
    {
        return $this->userFixture->getEmail();
    }
}
