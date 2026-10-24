<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony;

use App\Security\Domain\Exception\ExpiredTokenException;
use App\Security\Domain\Exception\InvalidTokenException;
use App\Security\Domain\Service\TokenEncoderDecoder;
use App\Security\Infrastructure\Symfony\UserProvider\AppUserProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class JwtAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private const string AUTHORIZATION_HEADER = 'Authorization';
    private const string BEARER_PREFIX = 'Bearer ';

    public function __construct(
        private TokenEncoderDecoder $tokenEncoderDecoder,
        private AppUserProvider $userProvider
    ) {
    }

    public function supports(Request $request): ?bool
    {
        $header = $request->headers->get(self::AUTHORIZATION_HEADER);

        return $header !== null && str_starts_with($header, self::BEARER_PREFIX);
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr($request->headers->get(self::AUTHORIZATION_HEADER), strlen(self::BEARER_PREFIX));

        try {
            $payload = $this->tokenEncoderDecoder->decode($token);
        } catch (ExpiredTokenException|InvalidTokenException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage(), [], 0, $exception);
        }

        if (empty($payload['id'])) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }

        // Tokens issued before token versioning carry no claim: they count as version 1.
        $tokenVersion = (int) ($payload['token_version'] ?? 1);

        return new SelfValidatingPassport(
            new UserBadge((string) $payload['id'], function (string $identifier) use ($tokenVersion) {
                $user = $this->userProvider->loadUserByIdentifier($identifier);

                // A password change bumps the user's token version, which revokes every token issued earlier.
                if ($user->getTokenVersion()->value() !== $tokenVersion) {
                    throw new CustomUserMessageAuthenticationException('Invalid token');
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $previous = $exception->getPrevious();

        if ($previous instanceof ExpiredTokenException || $previous instanceof InvalidTokenException) {
            return $this->errorResponse($previous->errorCode(), $previous->getMessage());
        }

        return $this->errorResponse('security.unauthenticated', 'Invalid credentials.');
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return $this->errorResponse('security.unauthenticated', 'Authentication required.');
    }

    private function errorResponse(string $type, string $message): JsonResponse
    {
        return new JsonResponse([
            'type' => $type,
            'message' => $message,
            'params' => [],
            'status' => Response::HTTP_UNAUTHORIZED,
        ], Response::HTTP_UNAUTHORIZED);
    }
}
