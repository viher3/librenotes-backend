<?php

namespace App\Core\Infrastructure\Exception;

use App\Core\Domain\Exception\BadRequest;
use App\Core\Domain\Exception\CriticalException;
use App\Core\Domain\Exception\DomainError;
use App\Core\Domain\Exception\ExceptionsStatusCodeHandler;
use App\Core\Domain\Exception\InvalidArgumentException;
use App\Core\Domain\Exception\InvalidPartialUpdateFieldsException;
use App\Core\Domain\Exception\InvalidUserPrivileges;
use App\Core\Domain\Exception\InvalidValueException;
use App\Core\Domain\Exception\NotFoundException;
use App\Core\Domain\Exception\Unauthorized;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use TypeError;

class ApiExceptionsHttpStatusCodeHandler implements ExceptionsStatusCodeHandler
{
    /** @var array<class-string, int> */
    private array $exceptions = [
        NotFoundHttpException::class => Response::HTTP_NOT_FOUND,
        MethodNotAllowedHttpException::class => Response::HTTP_METHOD_NOT_ALLOWED,
        TypeError::class => Response::HTTP_BAD_REQUEST,
        BadRequest::class => Response::HTTP_BAD_REQUEST,
        InvalidUserPrivileges::class => Response::HTTP_FORBIDDEN,
        Unauthorized::class => Response::HTTP_UNAUTHORIZED,
        ValidationFailedException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
        NotFoundException::class => Response::HTTP_NOT_FOUND,
        InvalidArgumentException::class => Response::HTTP_BAD_REQUEST,
        InvalidValueException::class => Response::HTTP_BAD_REQUEST,
        InvalidPartialUpdateFieldsException::class => Response::HTTP_BAD_REQUEST,
        CriticalException::class => Response::HTTP_INTERNAL_SERVER_ERROR,
        DomainError::class => Response::HTTP_BAD_REQUEST,
    ];

    public function register(string $exceptionClass, int $statusCode): void
    {
        $this->exceptions[$exceptionClass] = $statusCode;
    }

    public function resolve(string $exceptionClass): ?int
    {
        if (array_key_exists($exceptionClass, $this->exceptions)) {
            return $this->exceptions[$exceptionClass];
        }

        foreach (class_parents($exceptionClass) ?: [] as $parentClass) {
            if (array_key_exists($parentClass, $this->exceptions)) {
                return $this->exceptions[$parentClass];
            }
        }

        foreach (class_implements($exceptionClass) ?: [] as $interface) {
            if (array_key_exists($interface, $this->exceptions)) {
                return $this->exceptions[$interface];
            }
        }

        return null;
    }
}
