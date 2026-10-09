<?php

namespace App\Core\Infrastructure\Framework\Symfony\Controller;

use App\Core\Application\Command\Command;
use App\Core\Application\Command\CommandResponse;
use App\Core\Application\Query\Query;
use App\Core\Application\Query\QueryResponse;
use App\Core\Infrastructure\Bus\Command\SymfonyCommandBus;
use App\Core\Infrastructure\Bus\Command\SymfonyTransactionalCommandBus;
use App\Core\Infrastructure\Bus\Query\SymfonyQueryBus;
use App\Core\Infrastructure\Exception\ApiExceptionsHttpStatusCodeHandler;
use App\Core\Infrastructure\Filter\ApiFilterCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use function Lambdish\Phunctional\each;

abstract class BaseApiController extends AbstractController
{
    public const SEARCH_PARAM_PAGE_NAME = 'page';
    public const SEARCH_PARAM_SIZE_NAME = 'size';
    public const SEARCH_PARAM_ORDER_BY_NAME = 'orderBy';
    public const SEARCH_PARAM_ORDER_DIRECTION_NAME = 'orderDirection';

    public function __construct(
        private SymfonyQueryBus $queryBus,
        private SymfonyCommandBus $commandBus,
        private SymfonyTransactionalCommandBus $transactionalCommandBus,
        private ApiExceptionsHttpStatusCodeHandler $exceptionHandler
    )
    {
        each($this->exceptionRegistry(), $this->exceptions());
    }

    abstract protected function exceptions(): array;

    private function exceptionRegistry(): callable
    {
        return function (int $httpCode, string $exception) {
            $this->exceptionHandler->register($exception, $httpCode);
        };
    }

    /**
     * @throws \Throwable
     */
    public function ask(Query $query) : QueryResponse
    {
        return $this->queryBus->__invoke($query);
    }

    /**
     * @throws \Throwable
     */
    public function dispatch(Command $command) : ?CommandResponse
    {
        return $this->commandBus->__invoke($command);
    }

    /**
     * @throws \Throwable
     */
    public function dispatchTransactional(Command $command) : ?CommandResponse
    {
        return $this->transactionalCommandBus->__invoke($command);
    }

    /**
     * Identifier of the authenticated user. Routes behind the firewall always have one; this guards the
     * rest instead of silently falling back to a placeholder identity.
     */
    protected function currentUserId(): string
    {
        $user = $this->getUser();
        if ($user === null) {
            throw new AccessDeniedException();
        }

        return $user->getUserIdentifier();
    }

    /**
     * @param Request $request
     * @return array
     */
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

    protected function getFilters(Request $request): array
    {
        $params = $this->getParams($request);
        return !empty($params['filters']) ? json_decode($params['filters'],true) : [];
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getBody(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }

    /**
     * @param Request $request
     * @param array $allowedFilters
     * @return ApiFilterCollection
     */
    protected function buildFilters(Request $request, array $allowedFilters = []): ApiFilterCollection
    {
        return RequestFiltersFactory::create(
            filters: $this->getFilters($request),
            allowedFilters: $allowedFilters
        );
    }

    public function validateInputData(string $jsonSchema, Request $request) : ?JsonResponse
    {
        $requestParamsJsonValidator = new RequestParamsJsonValidator($jsonSchema);
        $requestParamsJsonValidator->validate($this->getParams($request));

        if ($requestParamsJsonValidator->getErrors()) {
            return new JsonResponse([
                'errors' => $requestParamsJsonValidator->getErrors(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

    /**
     * @param array $params
     * @return array
     */
    public function getSearchParams(array $params): array
    {
        return [
            !empty($params[self::SEARCH_PARAM_PAGE_NAME]) ? (int) $params[self::SEARCH_PARAM_PAGE_NAME] : null,
            isset($params[self::SEARCH_PARAM_SIZE_NAME]) ? (int) $params[self::SEARCH_PARAM_SIZE_NAME] : null,
            $params[self::SEARCH_PARAM_ORDER_BY_NAME] ?? null,
            $params[self::SEARCH_PARAM_ORDER_DIRECTION_NAME] ?? null
        ];
    }
}
