<?php

declare(strict_types=1);

namespace App\User\Application\Delete;

use App\Core\Application\Command\CommandHandler;
use App\User\Domain\Exception\NotFoundUser;
use App\User\Domain\UserRepository;

class DeleteUserHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->userRepository->getById($command->id);

        if(!$user){
            throw new NotFoundUser($command->id);
        }

        $this->userRepository->delete($user);
    }
}
