<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\UserProvider;

use App\User\Domain\Model\User;
use App\User\Domain\Repository\UserRoleAssignmentRepository;
use App\User\Domain\Repository\UserRoleRepository;
use App\User\Domain\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
class AppUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserRoleAssignmentRepository $userRoleAssignmentRepository,
        private UserRoleRepository $userRoleRepository
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->getById($identifier);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        $user->grantRoles($this->resolveRoleNames($identifier));

        return $user;
    }

    /**
     * @return string[]
     */
    private function resolveRoleNames(string $userId): array
    {
        $roleNames = [];

        foreach ($this->userRoleAssignmentRepository->findActiveByUserId($userId) as $assignment) {
            $role = $this->userRoleRepository->findById($assignment->getUserRoleId()->value());

            if ($role !== null) {
                $roleNames[] = $role->getName()->value();
            }
        }

        return $roleNames;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}
