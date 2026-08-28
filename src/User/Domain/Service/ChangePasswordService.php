<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

use App\Security\Domain\Service\PasswordHasher;
use App\User\Domain\Exception\InvalidUserPassword;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Model\User;
use App\User\Domain\UserRepository;
use App\User\Domain\ValueObject\HashedPassword;

class ChangePasswordService
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher
    )
    {
    }

    /**
     * @throws NotFoundUser
     */
    public function changePassword(
        string $userId,
        string $newPassword,
        ?string $currentPassword = null
    ): void
    {
        $user = $this->userRepository->getById($userId);

        if (!$user) {
            throw new NotFoundUser($userId);
        }

        if($currentPassword && !$this->validateUserCurrentPassword($user, $currentPassword)) {
            throw new InvalidUserPassword();
        }

        $hashedPassword = $this->passwordHasher->hash($user, $newPassword);
        $user->changePassword(new HashedPassword($hashedPassword));
        $this->userRepository->save($user);
    }

    /**
     * Validate if the provided current password matches the user's existing password.
     * @param User $user
     * @param string $currentPassword
     * @return bool
     */
    public function validateUserCurrentPassword(User $user, string $currentPassword): bool
    {
        return $this->passwordHasher->verify($user, $currentPassword);
    }

    /**
     * Validate if the provided current password matches the user's existing password.
     * @throws NotFoundUser
     */
    public function validateUserIdCurrentPassword(string $userId, string $currentPassword): bool
    {
        $user = $this->userRepository->getById($userId);

        if (!$user) {
            throw new NotFoundUser($userId);
        }

        return $this->validateUserCurrentPassword($user, $currentPassword);
    }
}
