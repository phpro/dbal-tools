<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Pager;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Phpro\DbalTools\Expression\Alias;
use Phpro\DbalTools\Expression\Count;
use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Expression\Over;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Query\CompositeQuery;
use Psl\Iter\Iterator;
use function Psl\Iter\first;
use function Psl\Math\ceil;
use function Psl\Vec\map;

/**
 * @template-implements Pager<array<string, mixed>>
 */
final readonly class WindowCountPager implements Pager
{
    /**
     * @param non-empty-string                    $countField
     * @param Iterator<int, array<string, mixed>> $iterator
     */
    private function __construct(
        private Pagination $pagination,
        private Iterator $iterator,
        private string $countField,
    ) {
    }

    /**
     * @param non-empty-string $countField
     */
    public static function create(
        Pagination $pagination,
        QueryBuilder|CompositeQuery $query,
        string $countField = 'total_results',
        ?Expression $countExpression = null,
    ): self {
        // First clone the query to avoid updates on the provided query.
        $query = clone $query;

        /**
         * @var QueryBuilder       $mainQuery
         * @var \Closure(): Result $executeQuery
         */
        [$mainQuery, $executeQuery] = match (true) {
            $query instanceof CompositeQuery => [$query->mainQuery(), $query->execute(...)],
            default => [$query, $query->executeQuery(...)],
        };

        $mainQuery->addSelect(
            new Alias(
                $countExpression ?? Over::aggregation(
                    new Count(SqlExpression::int(1)),
                    Over::fullWindow(),
                ),
                $countField,
            )->toSQL(),
        );
        $mainQuery->setMaxResults($pagination->limit);
        $mainQuery->setFirstResult(($pagination->page - 1) * $pagination->limit);
        $cursor = Iterator::from(function () use ($executeQuery): iterable {
            yield from $executeQuery()->iterateAssociative();
        });

        return new self(
            $pagination,
            $cursor,
            $countField,
        );
    }

    public function pagination(): Pagination
    {
        return $this->pagination;
    }

    public function totalResults(): int
    {
        $first = first($this->iterator) ?? [];

        return (int) ($first[$this->countField] ?? 0);
    }

    public function totalPages(): int
    {
        $totalResults = $this->totalResults();
        $limit = $this->pagination()->limit;
        if (!$totalResults) {
            return 1;
        }

        return (int) ceil($this->totalResults() / $limit);
    }

    public function traverse(\Closure $mapper): iterable
    {
        return map($this, $mapper);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->iterator;
    }
}
