<?php

namespace App\Core\Infrastructure\Ui\Controllers;

use App\Core\Application\Command\Command;
use App\Core\Application\Command\CommandResponse;
use App\Core\Application\Query\Query;
use App\Core\Application\Query\QueryResponse;
use App\Core\Infrastructure\Bus\Command\SymfonyCommandBus;
use App\Core\Infrastructure\Bus\Command\SymfonyTransactionalCommandBus;
use App\Core\Infrastructure\Bus\Query\SymfonyQueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

abstract class ApiController extends AbstractController
{
    public function __construct(
        private SymfonyQueryBus $queryBus,
        private SymfonyCommandBus $commandBus,
        private SymfonyTransactionalCommandBus $transactionalCommandBus
    ) {
    }

    abstract protected function exceptions(): array;

    /**
     * @throws Throwable
     */
    public function ask(Query $query): QueryResponse
    {
        return $this->queryBus->__invoke($query);
    }

    /**
     * @throws Throwable
     */
    public function dispatch(Command $command): CommandResponse
    {
        return $this->commandBus->__invoke($command);
    }

    /**
     * @throws Throwable
     */
    public function dispatchTransactional(Command $command): CommandResponse
    {
        return $this->transactionalCommandBus->__invoke($command);
    }

    protected function getParams(Request $request): array
    {
        $contentParams = [];

        if ($request->getContent()) {
            $contentParams = json_decode($request->getContent(), true) ?? [];
        }

        return array_merge(
            $contentParams,
            $request->request->all(),
            $request->query->all(),
        );
    }

    public function getFilters(Request $request): array
    {
        $queryParams = $this->getParams($request);
        return $queryParams['filter'] ?? [];
    }
}