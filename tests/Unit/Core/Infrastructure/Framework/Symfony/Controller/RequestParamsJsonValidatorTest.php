<?php

namespace App\Tests\Unit\Core\Infrastructure\Framework\Symfony\Controller;

use App\Core\Infrastructure\Framework\Symfony\Controller\RequestParamsJsonValidator;
use PHPUnit\Framework\TestCase;

class RequestParamsJsonValidatorTest extends TestCase
{
    private const SCHEMA = <<<'JSON'
        {
          "type": "array",
          "properties": {
            "email": {"type": "string", "format": "email"},
            "password": {"type": "string", "minLength": 8},
            "tags": {"type": "array", "items": {"type": "string"}},
            "meta": {"type": "object", "properties": {"n": {"type": "integer"}}, "required": ["n"]}
          },
          "required": ["email", "password"]
        }
        JSON;

    /**
     * @return string[]
     */
    private function errorsFor(array $params, string $schema = self::SCHEMA): array
    {
        $validator = new RequestParamsJsonValidator($schema);
        $validator->validate($params);

        return $validator->getErrors();
    }

    public function testEmptyBodyFailsWhenFieldsAreRequired(): void
    {
        $errors = $this->errorsFor([]);

        $this->assertContains('[email] The property email is required', $errors);
        $this->assertContains('[password] The property password is required', $errors);
    }

    public function testMissingRequiredFieldIsReported(): void
    {
        $errors = $this->errorsFor(['email' => 'a@b.co']);

        $this->assertSame(['[password] The property password is required'], $errors);
    }

    public function testInvalidValuesAreReported(): void
    {
        $errors = $this->errorsFor(['email' => 'nope', 'password' => 'short']);

        $this->assertCount(2, $errors);
    }

    public function testValidBodyPasses(): void
    {
        $this->assertSame([], $this->errorsFor(['email' => 'a@b.co', 'password' => 'longenough']));
    }

    public function testNestedListsAndObjectsAreValidated(): void
    {
        $valid = ['email' => 'a@b.co', 'password' => 'longenough', 'tags' => ['a', 'b'], 'meta' => ['n' => 1]];
        $this->assertSame([], $this->errorsFor($valid));

        $this->assertNotSame([], $this->errorsFor([...$valid, 'tags' => [1]]));
        $this->assertNotSame([], $this->errorsFor([...$valid, 'meta' => []]));
    }

    public function testSchemaWithoutPropertiesIsLeftUntouched(): void
    {
        $schema = '{"type": "array", "items": {"type": "string"}}';

        $this->assertSame([], $this->errorsFor(['a', 'b'], $schema));
        $this->assertNotSame([], $this->errorsFor([1], $schema));
    }
}
