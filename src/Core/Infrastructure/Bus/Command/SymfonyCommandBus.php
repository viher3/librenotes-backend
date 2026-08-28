<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Bus\Command;

use App\Core\Application\Command\Command;
use App\Core\Application\Command\CommandHandler;
use App\Core\Application\Command\CommandResponse;
use App\Core\Infrastructure\Bus\HandledStampReader;
use App\Core\Infrastructure\Bus\HandlerFailedExceptionUnwrapper;
use LogicException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class SymfonyCommandBus
{
    public function __construct(private MessageBusInterface $defaultCommandBus)
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(Command $command): ?CommandResponse
    {
        try {
            $envelope = $this->defaultCommandBus->dispatch($command);
            $result = HandledStampReader::result($envelope);
        } catch (HandlerFailedException $e) {
            throw HandlerFailedExceptionUnwrapper::unwrap($e);
        }

        if ($result !== null && !$result instanceof CommandResponse) {
            throw new LogicException(sprintf(
                'Command handler must return %s or null, %s given.',
                CommandResponse::class,
                get_debug_type($result)
            ));
        }

        return $result;
    }
}
