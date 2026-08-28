<?php

namespace App\Security\Domain\Service;

use App\Security\Domain\Exception\ExpiredTokenException;
use App\Security\Domain\Exception\InvalidTokenException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JwtService implements TokenEncoderDecoder
{
    private string $publicKey;
    private string $privateKey;
    private string $jwtKeyPath;
    private string $publicKeyAlgorithm;
    private int $publicKeyTtl;
    public const int CLOCK_SKEW = 60;

    /**
     * @param ParameterBagInterface $parameterBag
     * @param JwtKeyReader $jwtKeyReader
     * @throws \Exception
     */
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private JwtKeyReader $jwtKeyReader
    ) {
        $this->jwtKeyPath = (string) $this->parameterBag->get('jwt.key.path');
        $this->publicKeyAlgorithm = (string) $this->parameterBag->get('jwt.key.algorithm');
        $this->publicKeyTtl = (int) $this->parameterBag->get('jwt.key.ttl');

        $publicKey = $this->jwtKeyPath . '/public.pem';
        $privateKey = $this->jwtKeyPath . '/private.pem';

        if (!is_file($publicKey)) {
            throw new \Exception('Public Key file not found');
        }

        if (!is_file($privateKey)) {
            throw new \Exception('Private Key file not found');
        }

        $this->publicKey = $this->jwtKeyReader->getPublicKey($publicKey);
        $this->privateKey = $this->jwtKeyReader->getPrivateKey($privateKey);
    }

    public function encode(array $payload): string
    {
        $now = time();

        $payload = array_merge([
            'iat' => $now,
            'exp' => $now + $this->publicKeyTtl
        ], $payload);

        return JWT::encode(
            payload: $payload,
            key:     $this->privateKey,
            alg:     $this->publicKeyAlgorithm,
        );
    }

    /**
     * @throws ExpiredTokenException
     * @throws InvalidTokenException
     */
    public function decode(string $token): array
    {
        try {
            JWT::$leeway = self::CLOCK_SKEW;
            return (array)JWT::decode($token, new Key($this->publicKey, $this->publicKeyAlgorithm));
        } catch (ExpiredException) {
            throw new ExpiredTokenException();
        } catch (\Exception) {
            throw new InvalidTokenException();
        }
    }

    public function decodeWithoutVerification(string $token) : array
    {
        // Split the token into its parts
        $tokenParts = explode('.', $token);

        // Decode the payload (the second part of the token)
        $payload = base64_decode($tokenParts[1]);

        // Convert JSON payload to associative array
        return json_decode($payload, true);
    }
}