<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Pager;

use Doctrine\DBAL\Query\QueryBuilder;
use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Alias;
use Phpro\DbalTools\Expression\Count;
use Phpro\DbalTools\Expression\OrderBy;
use Phpro\DbalTools\Expression\Over;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Query\CompositeQuery;
use Psl\Iter\Iterator;
use Psl\Str;
use function Psl\invariant;
use function Psl\Iter\first;
use function Psl\Math\ceil;
use function Psl\Vec\map;

/**
 * Pages narrow rows and projects the fat ones only for the page it returns, in one statement.
 *
 * Reach for it when a row carries aggregated or joined objects; {@see WindowCountPager} stays the right
 * pager for a narrow row, which pays nothing for a CTE it does not need.
 *
 * PostgreSQL evaluates window functions before `ORDER BY`/`LIMIT` at the same query level, so
 * `COUNT(1) OVER()` buffers every matching row before the page is cut. Over a `jsonb_build_object`
 * projection those buffered rows are kilobytes wide and spill to temp files. Here the count window sits on
 * a narrow query selecting `$key` alone, which is then folded into a CTE and joined back to the fat
 * projection: the true total is still computed over the whole matching set, but out of `(key, total)` rows.
 *
 * @template-implements Pager<array<string, mixed>>
 */
final readonly class DeferredProjectionPager implements Pager
{
    /**
     * Fixed rather than a parameter: a caller registering a CTE of this name fails loudly at parse time,
     * which is the good failure, where a parameter would be one more thing every call site passes.
     */
    private const string PAGE_ALIAS = 'deferred_page';

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
     * You describe the list twice: which rows are in it, and what a row looks like.
     *
     * `$matchingKeys` decides which rows are in the list. Its main query selects `$key` and nothing else,
     * and carries every filter and scope join, for example `SELECT users.id FROM users WHERE ...`. Leave
     * out display columns, order and limit: the pager adds the total, the order and the page. Every CTE
     * registered on it stays available to the projection.
     *
     * `$key` is the column that main query selects, table qualified (`users.id`). The page is joined back
     * to the projection on it, so it must be unique per row: a duplicate multiplies the projected rows and
     * the total disagrees with the page.
     *
     * `$order` sorts the list. It is applied to both levels, since the narrow sort decides which rows the
     * page holds and the outer one the order they come back in.
     *
     * `$projection` decides what a row looks like. Return a new query builder that selects everything a row
     * shows `FROM` the table of `$key`, including any joins, aggregates and group by it needs. Leave out
     * filters, order and limit: the pager joins it onto the page, and a filter here would drop rows after
     * the total was counted. The closure receives the composite that will run and the name of the page
     * CTE. A projection that needs a CTE joins one registered on `$matchingKeys` through the composite, or
     * registers its own on it; one that needs none ignores both arguments.
     *
     * @param \Closure(CompositeQuery, non-empty-string): QueryBuilder $projection
     * @param non-empty-string                                         $countField
     */
    public static function create(
        Pagination $pagination,
        CompositeQuery $matchingKeys,
        Column $key,
        OrderBy $order,
        \Closure $projection,
        string $countField = 'total_results',
    ): self {
        invariant(null !== $key->from, 'The page key must be table qualified, so the projection join can be derived.');

        // First clone the composite to avoid updates on the provided query.
        $matchingKeys = clone $matchingKeys;
        $narrow = $matchingKeys->mainQuery();
        $narrow->addSelect(
            new Alias(
                Over::aggregation(new Count(SqlExpression::int(1)), Over::fullWindow()),
                $countField,
            )->toSQL(),
        );
        self::applyOrder($narrow, $order);
        $narrow->setMaxResults($pagination->limit);
        $narrow->setFirstResult(($pagination->page - 1) * $pagination->limit);

        /**
         * The narrow query becomes a CTE and the main query a fresh empty one, with every CTE the caller
         * registered preserved. A parameter bound on the narrow query survives this: the very same builder
         * moves into the `WITH` list, and `execute()` merges every registered CTE builder's parameters.
         */
        $folded = $matchingKeys->moveMainQueryToSubQuery(self::PAGE_ALIAS);

        $fat = $projection($folded, self::PAGE_ALIAS);

        /**
         * The count is read as a scalar sub-query rather than as a column of the joined CTE, so that a
         * projection which aggregates does not have to group by it. A plain column reference would be
         * neither aggregated nor functionally dependent on the group key, which PostgreSQL rejects, and
         * wrapping it in `MAX()` is not an option either: that would turn a non-aggregating projection
         * into an aggregate one and collapse the page to a single row. The value is identical on every row
         * of the page, so reading one is exact. The sub-query is the second reference to a CTE holding one
         * page of narrow rows, which costs nothing.
         */
        $fat->addSelect(
            new Alias(
                SqlExpression::parenthesized(sprintf(
                    'SELECT %s FROM %s LIMIT 1',
                    $folded->cteColumn(self::PAGE_ALIAS, $countField)->toSQL(),
                    self::PAGE_ALIAS,
                )),
                $countField,
            )->toSQL(),
        )->innerJoin(...$folded->joinOntoCte(
            self::PAGE_ALIAS,
            $key->from,
            $key,
        ));
        self::applyOrder($fat, $order);

        /**
         * The closure returns its projection rather than writing onto the empty main query, because
         * `QueryBuilder` keeps its select, from and join parts private with no setters: a pre-built query
         * cannot be transplanted, so it is installed wholesale instead.
         */
        $hydrated = $folded->map(static fn (QueryBuilder $mainQuery): QueryBuilder => $fat);

        return new self(
            $pagination,
            Iterator::from(
                /**
                 * @return iterable<int, array<string, mixed>>
                 */
                static function () use ($hydrated): iterable {
                    yield from $hydrated->execute()->iterateAssociative();
                },
            ),
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

        return (int) ceil($totalResults / $limit);
    }

    public function traverse(\Closure $mapper): iterable
    {
        return map($this, $mapper);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->iterator;
    }

    /**
     * `OrderBy` renders its own `ORDER BY` keyword, which `QueryBuilder::orderBy()` prepends in turn; the
     * builder joins its parts with a comma, so the whole expression goes in as one part.
     */
    private static function applyOrder(QueryBuilder $query, OrderBy $order): void
    {
        $query->orderBy(Str\strip_prefix($order->toSQL(), 'ORDER BY '));
    }
}
