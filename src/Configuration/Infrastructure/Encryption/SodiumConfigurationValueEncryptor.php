<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Encryption;

use App\Configuration\Domain\Exception\ConfigurationValueDecryptionFailedException;
use App\Configuration\Domain\Service\ConfigurationValueEncryptor;
use App\Configuration\Domain\Service\EncryptedValue;
use LogicException;

final class SodiumConfigurationValueEncryptor implements ConfigurationValueEncryptor
{
    /** @var array<int, string> raw 32-byte keys, keyed by version */
    private readonly array $keys;

    /**
     * @param array<string, string> $keysBase64 version => base64-encoded key, e.g. ['1' => '...']
     */
    public function __construct(
        array $keysBase64,
        private readonly int $currentVersion
    ) {
        $keys = [];
        foreach ($keysBase64 as $version => $base64Key) {
            $keys[(int) $version] = base64_decode($base64Key, true);
        }
        $this->keys = $keys;

        if (!isset($this->keys[$this->currentVersion])) {
            throw new LogicException(sprintf(
                'CONFIGURATION_ENCRYPTION_KEY_CURRENT_VERSION is %d, but the keyring has no key for that version.',
                $this->currentVersion
            ));
        }
    }

    public function encrypt(string $plaintext): EncryptedValue
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->keys[$this->currentVersion]);

        return new EncryptedValue(
            base64_encode($nonce . $ciphertext),
            $this->currentVersion
        );
    }

    public function decrypt(string $ciphertext, int $keyVersion): string
    {
        $key = $this->keys[$keyVersion] ?? throw new ConfigurationValueDecryptionFailedException($keyVersion);

        $raw = base64_decode($ciphertext, true);

        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new ConfigurationValueDecryptionFailedException($keyVersion);
        }

        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $box = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $plaintext = sodium_crypto_secretbox_open($box, $nonce, $key);

        if ($plaintext === false) {
            throw new ConfigurationValueDecryptionFailedException($keyVersion);
        }

        return $plaintext;
    }

    public function currentKeyVersion(): int
    {
        return $this->currentVersion;
    }
}
