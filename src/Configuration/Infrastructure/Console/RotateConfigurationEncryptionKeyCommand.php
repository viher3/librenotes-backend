<?php

declare(strict_types=1);

namespace App\Configuration\Infrastructure\Console;

use App\Configuration\Domain\Repository\ConfigurationHistoryRepository;
use App\Configuration\Domain\Repository\ConfigurationRepository;
use App\Configuration\Domain\Service\ConfigurationValueEncryptor;
use App\Configuration\Infrastructure\Cache\ConfigurationValueCache;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Re-encrypts every sensitive configuration (and history) value still on an old
 * key onto the current one. Deliberately bypasses the CQRS bus: this is a storage
 * format change, not a business change, so it must not emit ConfigurationValueChanged
 * or write a history row for every row it touches — see the "Key rotation" section
 * of the Configuration module design.
 *
 * Batched and idempotent: safe to stop and re-run. Rotated rows drop out of the
 * findSensitiveNotOnKeyVersion() result set, so each iteration re-queries from the
 * top rather than tracking an offset.
 */
#[AsCommand(
    name: 'configuration:rotate-encryption-key',
    description: 'Re-encrypts sensitive configuration values onto the current encryption key version'
)]
final class RotateConfigurationEncryptionKeyCommand extends Command
{
    private const int BATCH_SIZE = 200;

    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly ConfigurationHistoryRepository $historyRepository,
        private readonly ConfigurationValueEncryptor $encryptor,
        private readonly ConfigurationValueCache $cache
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $target = $this->encryptor->currentKeyVersion();

        $io->section(sprintf('Rotating onto key version %d', $target));

        $configurationsRotated = $this->rotateConfigurations($target);
        $io->text(sprintf('configurations: %d row(s) re-encrypted', $configurationsRotated));

        $historyRotated = $this->rotateHistory($target);
        $io->text(sprintf('configuration_history: %d row(s) re-encrypted', $historyRotated));

        $this->cache->clear();
        $io->text('Cache cleared.');

        $io->success(sprintf(
            'Rotation complete. Once verified, the previous key can be removed from CONFIGURATION_ENCRYPTION_KEYS.'
        ));

        return Command::SUCCESS;
    }

    private function rotateConfigurations(int $target): int
    {
        $count = 0;

        while ($batch = $this->configurationRepository->findSensitiveNotOnKeyVersion($target, self::BATCH_SIZE, 0)) {
            foreach ($batch as $configuration) {
                $plaintext = $this->encryptor->decrypt($configuration->getValue()->value(), $configuration->getKeyVersion());
                $reencrypted = $this->encryptor->encrypt($plaintext);

                $this->configurationRepository->rotateEncryptedValue(
                    $configuration->getId(),
                    $reencrypted->ciphertext,
                    $reencrypted->keyVersion,
                );

                $count++;
            }
        }

        return $count;
    }

    private function rotateHistory(int $target): int
    {
        $count = 0;

        while ($batch = $this->historyRepository->findSensitiveNotOnKeyVersion($target, self::BATCH_SIZE, 0)) {
            foreach ($batch as $entry) {
                $previousCiphertext = $entry->getPreviousValue()?->value();
                $previousKeyVersion = $entry->getPreviousValueKeyVersion();

                if ($previousKeyVersion !== null && $previousKeyVersion !== $target) {
                    $reencrypted = $this->encryptor->encrypt(
                        $this->encryptor->decrypt($previousCiphertext, $previousKeyVersion)
                    );
                    $previousCiphertext = $reencrypted->ciphertext;
                    $previousKeyVersion = $reencrypted->keyVersion;
                }

                $newCiphertext = $entry->getNewValue()?->value();
                $newKeyVersion = $entry->getNewValueKeyVersion();

                if ($newKeyVersion !== null && $newKeyVersion !== $target) {
                    $reencrypted = $this->encryptor->encrypt(
                        $this->encryptor->decrypt($newCiphertext, $newKeyVersion)
                    );
                    $newCiphertext = $reencrypted->ciphertext;
                    $newKeyVersion = $reencrypted->keyVersion;
                }

                $this->historyRepository->rotateEncryptedValues(
                    $entry->getId(),
                    $previousCiphertext,
                    $previousKeyVersion,
                    $newCiphertext,
                    $newKeyVersion,
                );

                $count++;
            }
        }

        return $count;
    }
}
