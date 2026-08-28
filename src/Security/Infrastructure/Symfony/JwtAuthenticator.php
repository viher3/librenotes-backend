<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony;

use App\Security\Domain\Exception\ExpiredTokenException;
use App\Security\Domain\Exception\InvalidTokenException;
use App\Security\Domain\Service\TokenEncoderDecoder;
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
        private TokenEncoderDecoder $tokenEncoderDecoder
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

        return new SelfValidatingPassport(new UserBadge((string) $payload['id']));
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
