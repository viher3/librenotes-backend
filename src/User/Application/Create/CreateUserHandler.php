<?php

declare(strict_types=1);

namespace App\User\Application\Create;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Bus\Event\EventBus;
use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\ContactData\EmailValueObject;
use App\Security\Domain\Service\PasswordHasher;
use App\User\Domain\Model\User;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\UserRepository;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\Password;
use App\User\Domain\ValueObject\UserId;

class CreateUserHandler implements TransactionalCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private EventBus $eventBus,
        private UniqueEmailSpecificationInterface $uniqueEmailSpecification,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(CreateUserCommand $command): void
    {
        $createdAt = $this->dateTimeProvider->now();

        $user = new User(
            uniqueEmailSpecification: $this->uniqueEmailSpecification,
            id: new UserId($command->id),
            email: new EmailValueObject($command->email),
            createdAt: $createdAt,
            active: $command->isActive,
            username: $command->username,
            lastLoginAt: null
        );
        $password = (new Password($this->passwordHasher, $command->password))->hashedValue($user);
        $user->changePassword(new HashedPassword($password));

        $this->userRepository->save($user);

        $this->eventBus->publish(...$user->pullDomainEvents());
    }
}
