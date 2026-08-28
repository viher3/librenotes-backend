<?php

namespace App\Tests\Unit\Core\Infrastructure\Framework\Symfony\EventSubscriber;

use App\Core\Infrastructure\Exception\ApiExceptionsHttpStatusCodeHandler;
use App\Core\Infrastructure\Framework\Symfony\EventSubscriber\ApiExceptionSubscriber;
use App\User\Domain\Exception\UserEmailAlreadyExists;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class ApiExceptionSubscriberTest extends TestCase
{
    private HttpKernelInterface $kernel;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(HttpKernelInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testMappedDomainErrorReturnsRegisteredStatusAndErrorCode(): void
    {
        $registry = new ApiExceptionsHttpStatusCodeHandler();
        $registry->register(UserEmailAlreadyExists::class, Response::HTTP_CONFLICT);

        $data = $this->responseDataFor(new UserEmailAlreadyExists('a@b.com'), registry: $registry);

        $this->assertSame(Response::HTTP_CONFLICT, $data['status']);
        $this->assertSame('user.email_already_exists', $data['code']);
        $this->assertSame(['value' => 'a@b.com'], $data['params']);
    }

    public function testUnmappedDomainErrorDefaultsToBadRequestInsteadOf500(): void
    {
        $data = $this->responseDataFor(new UserEmailAlreadyExists('a@b.com'));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $data['status']);
    }

    public function testHandlerFailedExceptionIsUnwrappedBeforeResolvingStatus(): void
    {
        $registry = new ApiExceptionsHttpStatusCodeHandler();
        $registry->register(UserEmailAlreadyExists::class, Response::HTTP_CONFLICT);

        $envelope = new Envelope(new \stdClass());
        $wrapped = new HandlerFailedException($envelope, [new UserEmailAlreadyExists('a@b.com')]);

        $data = $this->responseDataFor($wrapped, registry: $registry);

        $this->assertSame(Response::HTTP_CONFLICT, $data['status']);
        $this->assertSame('user.email_already_exists', $data['code']);
    }

    public function testHttpExceptionInterfaceStatusIsUsedWhenNotInRegistry(): void
    {
        $data = $this->responseDataFor(new ConflictHttpException('conflict'));

        $this->assertSame(Response::HTTP_CONFLICT, $data['status']);
    }

    public function testNonHttpExceptionCodeIsNeverUsedAsHttpStatus(): void
    {
        $data = $this->responseDataFor(new \RuntimeException('db auth failed', 1045));

        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $data['status']);
        $this->assertSame('internal_error', $data['code']);
    }

    public function testServerErrorMessageIsRedactedUnlessDebug(): void
    {
        $sensitive = new \RuntimeException('password=hunter2 SQLSTATE[28000]');

        $prodData = $this->responseDataFor($sensitive, debug: false);
        $this->assertSame('Internal server error', $prodData['message']);
        $this->assertArrayNotHasKey('exception', $prodData);

        $debugData = $this->responseDataFor($sensitive, debug: true);
        $this->assertSame('password=hunter2 SQLSTATE[28000]', $debugData['message']);
        $this->assertArrayHasKey('exception', $debugData);
    }

    public function testDomainErrorMessageIsNeverRedacted(): void
    {
        $data = $this->responseDataFor(new UserEmailAlreadyExists('a@b.com'), debug: false);

        $this->assertSame('Email already exists: a@b.com', $data['message']);
    }

    private function responseDataFor(
        \Throwable $exception,
        bool $debug = false,
        ?ApiExceptionsHttpStatusCodeHandler $registry = null
    ): array
    {
        $subscriber = new ApiExceptionSubscriber($registry ?? new ApiExceptionsHttpStatusCodeHandler(), $this->logger, $debug);

        $event = new ExceptionEvent($this->kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $exception);
        $subscriber($event);

        return json_decode($event->getResponse()->getContent(), true);
    }
}
