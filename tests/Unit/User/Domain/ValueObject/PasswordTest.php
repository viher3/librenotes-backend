<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\ValueObject;

use App\Security\Domain\Service\PasswordHasher;
use App\User\Domain\Exception\InvalidNewPassword;
use App\User\Domain\ValueObject\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PasswordTest extends TestCase
{
    /**
     * @return array<string, array{int, bool}>
     */
    public static function lengths(): array
    {
        return [
            '7 chars' => [7, false],
            '8 chars' => [8, true],
            '44 chars' => [44, true],
            '45 chars' => [45, false],
        ];
    }

    #[DataProvider('lengths')]
    public function testEnforcesLengthBoundaries(int $length, bool $valid): void
    {
        $hasher = $this->createMock(PasswordHasher::class);

        if (!$valid) {
            $this->expectException(InvalidNewPassword::class);
        }

        $password = new Password($hasher, str_repeat('a', $length));

        $this->assertSame($length, strlen($password->value()));
    }

    public function testExceptionExposesTheLimits(): void
    {
        try {
            new Password($this->createMock(PasswordHasher::class), 'short');
            $this->fail('Expected InvalidNewPassword');
        } catch (InvalidNewPassword $e) {
            $this->assertSame('user.invalid_new_password', $e->errorCode());
            $this->assertSame(['min' => 8, 'max' => 44], $e->params());
        }
    }
}
