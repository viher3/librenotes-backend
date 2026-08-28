<?php

namespace App\Tests\Unit\Core\Infrastructure\Exception;

use App\Core\Domain\Exception\NotFoundException;
use App\Core\Infrastructure\Exception\ApiExceptionsHttpStatusCodeHandler;
use App\User\Domain\Exception\NotFoundUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiExceptionsHttpStatusCodeHandlerTest extends TestCase
{
    private ApiExceptionsHttpStatusCodeHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ApiExceptionsHttpStatusCodeHandler();
    }

    public function testResolvesExactMatch(): void
    {
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->handler->resolve(NotFoundException::class));
    }

    public function testResolvesViaParentClassWhenSubclassNotRegistered(): void
    {
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->handler->resolve(NotFoundUser::class));
    }

    public function testExactMatchOverridesParentClassMatch(): void
    {
        $this->handler->register(NotFoundUser::class, Response::HTTP_GONE);

        $this->assertSame(Response::HTTP_GONE, $this->handler->resolve(NotFoundUser::class));
        $this->assertSame(Response::HTTP_NOT_FOUND, $this->handler->resolve(NotFoundException::class));
    }

    public function testReturnsNullWhenUnmapped(): void
    {
        $this->assertNull($this->handler->resolve(\RuntimeException::class));
    }

    public function testResolvesViaImplementedInterface(): void
    {
        $this->handler->register(\Countable::class, Response::HTTP_I_AM_A_TEAPOT);

        $exception = new class extends \Exception implements \Countable {
            public function count(): int
            {
                return 0;
            }
        };

        $this->assertSame(Response::HTTP_I_AM_A_TEAPOT, $this->handler->resolve(get_class($exception)));
    }
}
