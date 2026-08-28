<?php

declare(strict_types=1);

namespace App\User\Application\SignUp;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\Security\Domain\Service\PasswordHasher;
use App\User\Domain\Model\User;
use App\User\Domain\Service\UserNotifier;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\Password;
use App\User\Domain\ValueObject\UserId;

/**
 * Public self-registration. Unlike CreateUserHandler (admin-only), the resulting user
 * is always inactive until the activation link is used — isActive/isEmailValidated are
 * not caller-controlled here.
 */
class SignUpHandler implements TransactionalCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private EventBus $eventBus,
        private UniqueEmailSpecificationInterface $uniqueEmailSpecification,
        private DateTimeProvider $dateTimeProvider,
        private UserNotifier $userNotifier,
        private bool $sendActivationEmail
    ) {
    }

    public function __invoke(SignUpCommand $command): void
    {
        $now = $this->dateTimeProvider->now();

        $user = new User(
            uniqueEmailSpecification: $this->uniqueEmailSpecification,
            id: new UserId($command->id),
            email: new EmailValueObject($command->email),
            createdAt: $now,
            active: false,
            username: $command->username,
            lastLoginAt: null
        );
        $password = (new Password($this->passwordHasher, $command->password))->hashedValue($user);
        $user->changePassword(new HashedPassword($password));

        $activationToken = $user->requestEmailActivation($now);

        $this->userRepository->save($user);

        $this->eventBus->publish(...$user->pullDomainEvents());

        if ($this->sendActivationEmail) {
            $this->userNotifier->sendActivationEmail($user->getEmail(), $activationToken);
        }
    }
}
