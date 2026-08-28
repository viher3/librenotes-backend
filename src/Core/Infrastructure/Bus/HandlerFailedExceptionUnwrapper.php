<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Bus;

use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

final class HandlerFailedExceptionUnwrapper
{
    public static function unwrap(HandlerFailedException $exception): Throwable
    {
        $current = $exception;

        while ($current instanceof HandlerFailedException) {
            $previous = $current->getPrevious();

            if ($previous === null) {
                return $current;
            }

            $current = $previous;
        }

        return $current;
    }
}
