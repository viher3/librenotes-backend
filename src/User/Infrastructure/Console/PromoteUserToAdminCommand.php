<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Console;

use App\Core\Domain\Time\DateTimeProvider;
use App\Core\Domain\ValueObject\DateTimeValueObject;
use App\User\Domain\Model\UserRoleAssignment;
use App\User\Domain\Repository\UserRoleAssignmentRepository;
use App\User\Domain\Repository\UserRoleRepository;
use App\User\Domain\UserRepository;
use App\User\Domain\ValueObject\UserRoleAssignmentId;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Bootstrap for the first admin: there is no HTTP endpoint that can grant ROLE_ADMIN
 * (that endpoint would itself require ROLE_ADMIN to call), so this console command is
 * the only way in. The 'ROLE_ADMIN' UserRole row is expected to already exist, seeded
 * by the migration that ships with this feature.
 */
#[AsCommand(
    name: 'user:promote-admin',
    description: 'Grants ROLE_ADMIN to an existing user by email'
)]
final class PromoteUserToAdminCommand extends Command
{
    private const string ADMIN_ROLE_NAME = 'ROLE_ADMIN';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserRoleRepository $userRoleRepository,
        private readonly UserRoleAssignmentRepository $userRoleAssignmentRepository,
        private readonly DateTimeProvider $dateTimeProvider
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email of the user to promote');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) $input->getArgument('email');

        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            $io->error(sprintf('No user found with email "%s".', $email));

            return Command::FAILURE;
        }

        $role = $this->userRoleRepository->findByName(self::ADMIN_ROLE_NAME);

        if (!$role) {
            $io->error(sprintf(
                '"%s" role does not exist yet. Run the pending migrations first.',
                self::ADMIN_ROLE_NAME
            ));

            return Command::FAILURE;
        }

        $existing = $this->userRoleAssignmentRepository->findActiveByUserIdAndRoleId(
            $user->getId()->value(),
            $role->getId()->value()
        );

        if ($existing) {
            $io->note(sprintf('"%s" already has %s.', $email, self::ADMIN_ROLE_NAME));

            return Command::SUCCESS;
        }

        $this->userRoleAssignmentRepository->save(new UserRoleAssignment(
            id: new UserRoleAssignmentId(RamseyUuid::uuid4()->toString()),
            userId: $user->getId(),
            userRoleId: $role->getId(),
            assignedAt: new DateTimeValueObject($this->dateTimeProvider->now())
        ));

        $io->success(sprintf('Granted %s to "%s".', self::ADMIN_ROLE_NAME, $email));

        return Command::SUCCESS;
    }
}
