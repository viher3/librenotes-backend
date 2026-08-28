<?php

declare(strict_types=1);

namespace App\Core\Infrastructure\Persistence\Doctrine;

use App\Core\Domain\Aggregate\AggregateRoot;
use App\Core\Domain\Filter\DomainFilter;
use App\Core\Domain\Filter\FilterOperator;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template T of object
 * @extends ServiceEntityRepository<T>
 */
abstract class DoctrineRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    /**
     * @param class-string<T> $entityClass
     */
    public function __construct(
        private string $entityClass,
        EntityManagerInterface $entityManager,
        ManagerRegistry $registry
    ) {
        $this->entityManager = $entityManager;
        parent::__construct($registry, $entityClass);
    }

    /**
     * @return EntityManagerInterface
     */
    public function entityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @param AggregateRoot $entity
     * @return void
     */
    public function persist(AggregateRoot $entity): void
    {
        $this->entityManager()->persist($entity);
        $this->entityManager()->flush();
    }

    /**
     * @param AggregateRoot $entity
     * @return void
     */
    public function remove(AggregateRoot $entity): void
    {
        $this->entityManager()->remove($entity);
        $this->entityManager()->flush();
    }

    /**
     * @return EntityRepository<T>
     */
    public function getRepository(): EntityRepository
    {
        return $this->entityManager->getRepository($this->entityClass);
    }

    /**
     * @param QueryBuilder $qb
     * @param int $page
     * @param int $perPage
     * @return QueryBuilder
     */
    public function applyPagination(QueryBuilder $qb, int $page, int $perPage): QueryBuilder
    {
        $qb->setMaxResults($perPage);
        $qb->setFirstResult($perPage * ($page - 1));

        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $orderBy
     * @param string $orderDirection
     * @return QueryBuilder
     */
    public function applyOrderCustomAlias(
        string $alias,
        QueryBuilder $qb,
        string $orderBy,
        string $orderDirection = ''
    ): QueryBuilder {
        $orderDirection = ($orderDirection === 'desc') ? 'desc' : 'asc';
        $orderByWithAlias = $alias . '.' . $orderBy;
        $qb->orderBy($orderByWithAlias, $orderDirection);
        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $orderBy
     * @param string $orderDirection
     * @return QueryBuilder
     */
    protected function applyOrder(
        QueryBuilder $qb,
        string $orderBy,
        string $orderDirection = ''
    ): QueryBuilder {
        $orderDirection = ($orderDirection === 'desc') ? 'desc' : 'asc';
        $orderByWithAlias = $qb->getRootAliases()[0] . '.' . $orderBy;
        $qb->orderBy($orderByWithAlias, $orderDirection);
        return $qb;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $filter
     * @param string $filterAlias
     * @return QueryBuilder
     */
    public function applyFilter(
        QueryBuilder $qb,
        array $filter,
        string $filterAlias
    ): QueryBuilder {
        $filter = DomainFilter::fromArray($filter);
        $field = $filter->getField();
        $operator = $filter->getOperator();
        $value = $filter->getValue();
        $fieldWithAlias = $filterAlias . '.' . $field;
        $fieldParamName = $field . random_int(1, 99);

        match ($operator) {
            FilterOperator::LIKE => $qb->andWhere($fieldWithAlias . ' LIKE :' . $fieldParamName)->setParameter($fieldParamName, "%" . $value . "%"),
            FilterOperator::EQUALS => $qb->andWhere($fieldWithAlias . ' = :' . $fieldParamName)->setParameter($fieldParamName, $value),
            FilterOperator::GREATER_THAN => $qb->andWhere($fieldWithAlias . ' > :' . $fieldParamName)->setParameter($fieldParamName, $value),
            FilterOperator::LESS_THAN => $qb->andWhere($fieldWithAlias . ' < :' . $fieldParamName)->setParameter($fieldParamName, $value),
            FilterOperator::GREATER_OR_EQUAL_THAN => $qb->andWhere($fieldWithAlias . ' >= :' . $fieldParamName)->setParameter($fieldParamName, $value),
            FilterOperator::LESS_OR_EQUAL_THAN => $qb->andWhere($fieldWithAlias . ' <= :' . $fieldParamName)->setParameter($fieldParamName, $value),
            FilterOperator::IN => $qb->andWhere($fieldWithAlias . ' IN ( :' . $fieldParamName . ')')->setParameter($fieldParamName, $value),
            FilterOperator::NOT_IN => $qb->andWhere($fieldWithAlias . ' NOT IN ( :' . $fieldParamName . ')')->setParameter($fieldParamName, $value),
            FilterOperator::NULL =>  $qb->andWhere($fieldWithAlias . ' IS NULL'),
            FilterOperator::NOT_NULL =>  $qb->andWhere($fieldWithAlias . ' IS NOT NULL'),
            default => $qb
        };

        return $qb;
    }

    /**
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @param string|null $orderBy
     * @param string|null $orderDirection
     * @return array
     */
    public function search(
        array $filters,
        int $page,
        int $perPage,
        ?string $orderBy = null,
        ?string $orderDirection = null
    ): array {
        $qb = $this
            ->searchQueryBuilder($filters, $page, $perPage, $orderBy, $orderDirection);
        return $qb->getQuery()->execute();
    }

    /**
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @param string|null $orderBy
     * @param string|null $orderDirection
     * @return QueryBuilder
     */
    public function searchQueryBuilder(
        array $filters,
        int $page = 0,
        int $perPage = 0,
        ?string $orderBy = null,
        ?string $orderDirection = null,
        ?string $qbAlias = null
    ): QueryBuilder {
        $qbAlias = $qbAlias ?: 'u';
        $qb = $this->createQueryBuilder($qbAlias);

        foreach ($filters as $filter) {
            $this->applyFilter($qb, $filter, $qbAlias);
        }

        if ($orderBy && $orderDirection) {
            $this->applyOrderCustomAlias($qbAlias, $qb, $orderBy, $orderDirection);
        }

        if ($page && $perPage) {
            $this->applyPagination($qb, $page, $perPage);
        }

        return $qb;
    }

    /**
     * @param array $filters
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function searchCount(array $filters): int
    {
        $qb = $this->searchQueryBuilder($filters);
        $qb->select('count(1) as total');
        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
