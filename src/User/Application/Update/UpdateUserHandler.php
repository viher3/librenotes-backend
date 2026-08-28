<?php

declare(strict_types=1);

namespace App\User\Application\Update;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\Specification\UniqueEmailSpecificationInterface;
use App\User\Domain\UserRepository;

class UpdateUserHandler implements TransactionalCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private UniqueEmailSpecificationInterface $uniqueEmailSpecification,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->userRepository->getById($command->id);

        if(!$user){
            throw new NotFoundUser($command->id);
        }

        $user->update(
            $command->fields,
            $this->uniqueEmailSpecification
        );

        $this->userRepository->save($user);
    }
}
