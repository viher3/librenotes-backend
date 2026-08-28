<?php

declare(strict_types=1);

namespace App\Tests\Unit\Configuration\Infrastructure\Encryption;

use App\Configuration\Domain\Exception\ConfigurationValueDecryptionFailedException;
use App\Configuration\Infrastructure\Encryption\SodiumConfigurationValueEncryptor;
use PHPUnit\Framework\TestCase;

class SodiumConfigurationValueEncryptorTest extends TestCase
{
    private function key(): string
    {
        return base64_encode(sodium_crypto_secretbox_keygen());
    }

    public function testEncryptThenDecryptRoundTrips(): void
    {
        $encryptor = new SodiumConfigurationValueEncryptor(['1' => $this->key()], 1);

        $encrypted = $encryptor->encrypt('sk_live_secret');

        self::assertSame(1, $encrypted->keyVersion);
        self::assertNotSame('sk_live_secret', $encrypted->ciphertext);
        self::assertSame('sk_live_secret', $encryptor->decrypt($encrypted->ciphertext, $encrypted->keyVersion));
    }

    public function testEachEncryptionUsesAFreshNonce(): void
    {
        $encryptor = new SodiumConfigurationValueEncryptor(['1' => $this->key()], 1);

        $first = $encryptor->encrypt('same plaintext');
        $second = $encryptor->encrypt('same plaintext');

        self::assertNotSame($first->ciphertext, $second->ciphertext);
    }

    public function testCanDecryptAnOlderKeyVersionStillPresentInTheKeyring(): void
    {
        $oldKey = $this->key();
        $newKey = $this->key();

        $rotatingOut = new SodiumConfigurationValueEncryptor(['1' => $oldKey], 1);
        $encryptedUnderOldKey = $rotatingOut->encrypt('legacy secret');

        // Simulates the state during a rotation: both keys present, current bumped to 2.
        $duringRotation = new SodiumConfigurationValueEncryptor(['1' => $oldKey, '2' => $newKey], 2);

        self::assertSame(
            'legacy secret',
            $duringRotation->decrypt($encryptedUnderOldKey->ciphertext, $encryptedUnderOldKey->keyVersion)
        );
        self::assertSame(2, $duringRotation->currentKeyVersion());
    }

    public function testDecryptingWithAKeyVersionNotInTheRingThrows(): void
    {
        $encryptor = new SodiumConfigurationValueEncryptor(['1' => $this->key()], 1);
        $encrypted = $encryptor->encrypt('secret');

        // The key that encrypted this was since removed from the ring.
        $afterKeyRemoved = new SodiumConfigurationValueEncryptor(['2' => $this->key()], 2);

        $this->expectException(ConfigurationValueDecryptionFailedException::class);

        $afterKeyRemoved->decrypt($encrypted->ciphertext, $encrypted->keyVersion);
    }

    public function testTamperedCiphertextFailsLoudlyRatherThanReturningGarbage(): void
    {
        $encryptor = new SodiumConfigurationValueEncryptor(['1' => $this->key()], 1);
        $encrypted = $encryptor->encrypt('secret');

        $tampered = substr($encrypted->ciphertext, 0, -4) . 'AAAA';

        $this->expectException(ConfigurationValueDecryptionFailedException::class);

        $encryptor->decrypt($tampered, $encrypted->keyVersion);
    }

    public function testConstructingWithACurrentVersionMissingFromTheRingFailsFast(): void
    {
        $this->expectException(\LogicException::class);

        new SodiumConfigurationValueEncryptor(['1' => $this->key()], 2);
    }
}
