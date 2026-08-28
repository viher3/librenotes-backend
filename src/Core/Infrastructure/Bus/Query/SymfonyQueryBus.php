<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Bus\Query;

use App\Core\Application\Query\Query;
use App\Core\Application\Query\QueryHandler;
use App\Core\Application\Query\QueryResponse;
use App\Core\Infrastructure\Bus\HandledStampReader;
use App\Core\Infrastructure\Bus\HandlerFailedExceptionUnwrapper;
use LogicException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final class SymfonyQueryBus
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    /**
     * @throws Throwable
     */
    public function __invoke(Query $query): QueryResponse
    {
        try {
            $envelope = $this->queryBus->dispatch($query);
            $result = HandledStampReader::result($envelope);
        } catch (HandlerFailedException $e) {
            throw HandlerFailedExceptionUnwrapper::unwrap($e);
        }

        if (!$result instanceof QueryResponse) {
            throw new LogicException(sprintf(
                'Query handler must return a %s instance, %s given.',
                QueryResponse::class,
                get_debug_type($result)
            ));
        }

        return $result;
    }
}
