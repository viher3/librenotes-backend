<?php

namespace App\Core\Infrastructure\Framework\Symfony\EventSubscriber;

use App\Core\Domain\Exception\CriticalException;
use App\Core\Domain\Exception\DomainError;
use App\Core\Infrastructure\Bus\HandlerFailedExceptionUnwrapper;
use App\Core\Infrastructure\Exception\ApiExceptionsHttpStatusCodeHandler;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

class ApiExceptionSubscriber
{
    private const string DEFAULT_5XX_CODE = 'internal_error';
    private const string DEFAULT_5XX_MESSAGE = 'Internal server error';

    public function __construct(
        private ApiExceptionsHttpStatusCodeHandler $exceptionHandler,
        private LoggerInterface $logger,
        private bool $debug = false
    )
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HandlerFailedException) {
            $exception = HandlerFailedExceptionUnwrapper::unwrap($exception);
        }

        $statusCode = $this->resolveStatusCode($exception);
        $responseData = $this->buildResponseData($exception, $statusCode);

        $this->log($exception, $statusCode, $responseData);

        $headers = [];
        $retryAfter = $responseData['params']['retry_after'] ?? null;
        if ($statusCode === Response::HTTP_TOO_MANY_REQUESTS && is_int($retryAfter)) {
            $headers['Retry-After'] = (string) $retryAfter;
        }

        $event->setResponse(new JsonResponse($responseData, $statusCode, $headers));
    }

    private function resolveStatusCode(Throwable $exception): int
    {
        $resolved = $this->exceptionHandler->resolve(get_class($exception));
        if ($resolved !== null) {
            return $resolved;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function buildResponseData(Throwable $exception, int $statusCode): array
    {
        if ($exception instanceof DomainError) {
            return [
                'code' => $exception->errorCode(),
                'message' => $exception->getMessage(),
                'params' => $exception->params(),
                'status' => $statusCode,
            ];
        }

        $isServerError = $statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR;

        $responseData = [
            'code' => $isServerError ? self::DEFAULT_5XX_CODE : $this->shortCode($exception),
            'message' => ($isServerError && !$this->debug) ? self::DEFAULT_5XX_MESSAGE : $exception->getMessage(),
            'params' => [],
            'status' => $statusCode,
        ];

        if ($isServerError && $this->debug) {
            $responseData['exception'] = [
                'class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return $responseData;
    }

    private function shortCode(Throwable $exception): string
    {
        $shortName = (new ReflectionClass($exception))->getShortName();

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));
    }

    private function log(Throwable $exception, int $statusCode, array $responseData): void
    {
        $context = array_merge($responseData, ['exception' => $exception]);

        if ($exception instanceof CriticalException || $statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            $this->logger->critical($exception->getMessage(), $context);

            return;
        }

        $this->logger->warning($exception->getMessage(), $context);
    }
}
