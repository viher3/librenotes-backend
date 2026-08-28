<?php

declare(strict_types=1);

namespace App\Tests\Unit\Configuration\Domain\Service;

use App\Configuration\Domain\Exception\InvalidConfigurationValueException;
use App\Configuration\Domain\Model\ValueObject\ConfigurationType;
use App\Configuration\Domain\Service\ConfigurationValueCaster;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConfigurationValueCasterTest extends TestCase
{
    private ConfigurationValueCaster $caster;

    protected function setUp(): void
    {
        $this->caster = new ConfigurationValueCaster();
    }

    #[DataProvider('validValuesProvider')]
    public function testAssertValidAcceptsValidValues(string $value, string $type): void
    {
        $this->caster->assertValid($value, new ConfigurationType($type));
        $this->addToAssertionCount(1);
    }

    public static function validValuesProvider(): array
    {
        return [
            'string is always valid' => ['anything at all', ConfigurationType::STRING],
            'positive integer' => ['42', ConfigurationType::INTEGER],
            'negative integer' => ['-7', ConfigurationType::INTEGER],
            'boolean true literal' => ['true', ConfigurationType::BOOLEAN],
            'boolean 1 literal' => ['1', ConfigurationType::BOOLEAN],
            'json object' => ['{"a":1}', ConfigurationType::JSON],
            'json array' => ['[1,2,3]', ConfigurationType::JSON],
        ];
    }

    #[DataProvider('invalidValuesProvider')]
    public function testAssertValidRejectsInvalidValues(string $value, string $type): void
    {
        $this->expectException(InvalidConfigurationValueException::class);

        $this->caster->assertValid($value, new ConfigurationType($type));
    }

    public static function invalidValuesProvider(): array
    {
        return [
            'not an integer' => ['not-a-number', ConfigurationType::INTEGER],
            'float is not an integer' => ['1.5', ConfigurationType::INTEGER],
            'not a boolean literal' => ['yes', ConfigurationType::BOOLEAN],
            'malformed json' => ['{not json', ConfigurationType::JSON],
        ];
    }

    public function testCastConvertsToTheDeclaredPhpType(): void
    {
        self::assertSame('hello', $this->caster->cast('hello', new ConfigurationType(ConfigurationType::STRING)));
        self::assertSame(42, $this->caster->cast('42', new ConfigurationType(ConfigurationType::INTEGER)));
        self::assertTrue($this->caster->cast('true', new ConfigurationType(ConfigurationType::BOOLEAN)));
        self::assertFalse($this->caster->cast('false', new ConfigurationType(ConfigurationType::BOOLEAN)));
        self::assertSame(['a' => 1], $this->caster->cast('{"a":1}', new ConfigurationType(ConfigurationType::JSON)));
    }
}
