<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

use App\Core\Domain\Time\DateTimeProvider;
use App\Security\Domain\Service\PasswordHasher;
use App\User\Domain\Exception\InvalidNewPassword;
use App\User\Domain\Exception\InvalidUserPassword;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Exception\PasswordUnchanged;
use App\User\Domain\Model\User;
use App\User\Domain\UserRepository;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\Password;

class ChangePasswordService
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private DateTimeProvider $dateTimeProvider
    )
    {
    }

    /**
     * Changes the user's password. The current password is always verified.
     *
     * @throws NotFoundUser
     * @throws InvalidUserPassword when the current password does not match
     * @throws InvalidNewPassword when the new password breaks the password rules
     * @throws PasswordUnchanged when the new password equals the current one
     */
    public function changePassword(
        string $userId,
        string $newPassword,
        string $currentPassword
    ): User
    {
        $user = $this->userRepository->getById($userId);

        if (!$user) {
            throw new NotFoundUser($userId);
        }

        if (!$this->validateUserCurrentPassword($user, $currentPassword)) {
            throw new InvalidUserPassword();
        }

        $password = new Password($this->passwordHasher, $newPassword);

        if ($newPassword === $currentPassword) {
            throw new PasswordUnchanged();
        }

        $user->updatePassword(
            new HashedPassword($password->hashedValue($user)),
            $this->dateTimeProvider->now()
        );
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Validate if the provided current password matches the user's existing password.
     */
    public function validateUserCurrentPassword(User $user, string $currentPassword): bool
    {
        return $this->passwordHasher->verify($user, $currentPassword);
    }
}
