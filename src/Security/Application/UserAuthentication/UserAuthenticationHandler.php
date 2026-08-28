<?php

namespace App\Security\Application\UserAuthentication;

use App\Core\Application\Command\CommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Security\Domain\Exception\BadCredentialsException;
use App\Security\Domain\Exception\InactiveUserException;
use App\Security\Domain\Service\LoginRateLimiter;
use App\Security\Domain\Service\PasswordHasher;
use App\Security\Domain\Service\RefreshTokenGenerator;
use App\Security\Domain\Service\TokenEncoderDecoder;
use App\User\Domain\UserRepository;
use Psr\Log\LoggerInterface;
use Random\RandomException;

class UserAuthenticationHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private TokenEncoderDecoder $tokenEncoderDecoder,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private LoginRateLimiter $loginRateLimiter,
        private LoggerInterface $logger,
        private DateTimeProvider $dateTimeProvider,
    )
    {
    }

    /**
     * @throws RandomException
     */
    public function __invoke(UserAuthenticationCommand $command) : UserAuthenticationResponse
    {
        $this->loginRateLimiter->consumeLoginAttempt('ip:' . $command->ip);
        $this->loginRateLimiter->consumeLoginAttempt('email:' . $command->email);

        $user = $this->userRepository->findByEmail($command->email);

        if(!$user){
            throw new BadCredentialsException();
        }

        // Check password
        if(!$this->passwordHasher->verify($user, $command->password)){
            throw new BadCredentialsException();
        }

        if(!$user->isActive()){
            throw new InactiveUserException();
        }

        try{
            $user->loggedAt($this->dateTimeProvider->now());
            $this->userRepository->save($user);
        }catch (\Exception $e){
            $this->logger->error('Error updating last user login value: ' . $e->getMessage());
        }

        // access token
        $accessToken = $this->tokenEncoderDecoder->encode([
            'id' => $user->getId()->value()
        ]);

        // refresh token
        $refreshToken = $this->refreshTokenGenerator->generate($user->getId());

        return new UserAuthenticationResponse($accessToken, $refreshToken->value);
    }
}
