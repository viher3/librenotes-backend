<?php

namespace App\User\Application\ChangePassword;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\User\Domain\Exception\ErrorUserChangePassword;
use App\User\Domain\Exception\InvalidUserPassword;
use App\User\Domain\Service\ChangePasswordService;

class ChangePasswordHandler implements TransactionalCommandHandler
{
    public function __construct(
        private ChangePasswordService $changePasswordService
    )
    {
    }

    public function __invoke(ChangePasswordCommand $command) : void
    {
        try{
            // TODO: if user is ADMIN, allow changing password without old password
            $this->changePasswordService->changePassword(
                userId: $command->userId,
                newPassword: $command->newPassword,
                currentPassword: $command->oldPassword
            );
        }catch (InvalidUserPassword $e){
            throw $e;
        }catch (\Exception $e){
            throw ErrorUserChangePassword::fromPrevious($e);
        }
    }
}