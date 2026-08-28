<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Bus;

use LogicException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class HandledStampReader
{
    public static function result(Envelope $envelope): mixed
    {
        $stamp = $envelope->last(HandledStamp::class);

        if (!$stamp instanceof HandledStamp) {
            throw new LogicException('No handler produced a result for this message; check the bus wiring.');
        }

        return $stamp->getResult();
    }
}
