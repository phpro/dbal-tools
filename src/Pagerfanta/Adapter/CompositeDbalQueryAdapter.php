<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Pagerfanta\Adapter;

use Doctrine\DBAL\Query\QueryBuilder;
use Pagerfanta\Adapter\AdapterInterface;
use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Count;
use Phpro\DbalTools\Query\CompositeQuery;

/**
 * @template T
 *
 * @template-implements AdapterInterface<T>
 */
final readonly class CompositeDbalQueryAdapter implements AdapterInterface
{
    /**
     * @param (\Closure(CompositeQuery): CompositeQuery) $countQueryBuilderModifier
     */
    public function __construct(
        private CompositeQuery $query,
        private \Closure $countQueryBuilderModifier,
    ) {
    }

    public static function default(CompositeQuery $query, Column $countColumn): self
    {
        return new self($query, static fn (CompositeQuery $query) => $query->map(
            static fn (QueryBuilder $queryBuilder) => $queryBuilder
                ->resetGroupBy()
                ->select($countColumn->apply(
                    static fn ($col) => new Count($col),
                    'total_results'
                ))
                ->orderBy('total_results')
                ->setMaxResults(1)
        ));
    }

    public function getNbResults(): int
    {
        $query = ($this->countQueryBuilderModifier)($this->query);

        /** @var int<0,max> */
        return (int) $query->execute()->fetchOne();
    }

    public function getSlice(int $offset, int $length): iterable
    {
        $query = $this->query->map(
            static fn (QueryBuilder $queryBuilder) => $queryBuilder
                ->setMaxResults($length)
                ->setFirstResult($offset)
        );

        /** @var \Generator<array-key, T, mixed, void> $cursor */
        $cursor = $query->execute()->iterateAssociative();

        yield from $cursor;
    }
}
