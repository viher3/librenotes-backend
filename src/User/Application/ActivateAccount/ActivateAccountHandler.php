<?php

declare(strict_types=1);

namespace App\User\Application\ActivateAccount;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\User\Domain\Exception\ActivationTokenExpired;
use App\User\Domain\Exception\InvalidActivationToken;
use App\User\Domain\UserRepository;

class ActivateAccountHandler implements TransactionalCommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(ActivateAccountCommand $command): void
    {
        $user = $this->userRepository->findByActivationToken($command->token);

        if (!$user) {
            throw new InvalidActivationToken();
        }

        if (!$user->isActivationTokenValid($this->dateTimeProvider->now())) {
            throw new ActivationTokenExpired();
        }

        $user->activate();

        $this->userRepository->save($user);
    }
}
