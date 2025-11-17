<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\UnionType;
use Doctrine\DBAL\Result;
use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Comparison;
use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Schema\Table;
use Psl\Str;
use function Psl\Dict\map;
use function Psl\Dict\merge;
use function Psl\invariant;
use function Psl\Vec\map_with_key;

/**
 * @psalm-import-type JoinInfo from Table
 *
 * @psalm-type With = array{0: QueryBuilder, 1 ?: CompositeSubQueryOptions|null}
 */
final class CompositeQuery implements Expression
{
    private Connection $connection;
    private QueryBuilder $query;

    /**
     * @var array<non-empty-string, With>
     */
    private array $with;

    private CompositeQueryOptions $options;

    /**
     * @param QueryBuilder                               $query
     * @param array<non-empty-string, QueryBuilder|With> $with
     */
    public function __construct(
        Connection $connection,
        QueryBuilder $query,
        array $with,
        ?CompositeQueryOptions $options = null,
    ) {
        $this->connection = $connection;
        $this->query = $query;
        $this->with = map(
            $with,
            /**
             * @param QueryBuilder|With $query
             *
             * @return With
             */
            fn (QueryBuilder|array $query): array => match (true) {
                is_array($query) => $query,
                default => [$query],
            }
        );
        $this->options = $options ?? CompositeQueryOptions::default();
    }

    public static function from(Connection $connection): self
    {
        return new self(
            $connection,
            $connection->createQueryBuilder(),
            [],
            CompositeQueryOptions::default(),
        );
    }

    public function mainQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * @param non-empty-string              $name
     * @param CompositeSubQueryOptions|null $subQueryOptions
     */
    public function moveMainQueryToSubQuery(string $name, ?CompositeSubQueryOptions $subQueryOptions = null): self
    {
        return new self(
            $this->connection,
            $this->connection->createQueryBuilder(),
            merge(
                $this->with,
                [$name => [$this->mainQuery(), $subQueryOptions]]
            ),
            $this->options,
        );
    }

    /**
     * @param non-empty-string $name
     *
     * @return QueryBuilder
     */
    public function subQuery(string $name): QueryBuilder
    {
        invariant(array_key_exists($name, $this->with), sprintf('Subquery "%s" does not exist.', $name));

        $query = $this->with[$name];

        return $query[0];
    }

    /**
     * @param non-empty-string $name
     */
    public function hasSubQuery(string $name): bool
    {
        return array_key_exists($name, $this->with);
    }

    /**
     * @param non-empty-string $name
     */
    public function createSubQuery(string $name, ?CompositeSubQueryOptions $subQueryOptions = null): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();

        $this->addSubQuery($name, $query, $subQueryOptions);

        return $query;
    }

    /**
     * @param non-empty-string $name
     */
    public function addSubQuery(string $name, QueryBuilder $queryBuilder, ?CompositeSubQueryOptions $subQueryOptions = null): self
    {
        $this->with[$name] = [$queryBuilder, $subQueryOptions];

        return $this;
    }

    /**
     * @param non-empty-string $name
     *
     * @return array{0: QueryBuilder, 1: QueryBuilder} - Returns a tuple of (BaseQuery, RecursiveQuery)
     */
    public function createRecursiveSubQuery(
        string $name,
        UnionType $type = UnionType::ALL,
    ): array {
        $recursiveQuery = $this->connection->createQueryBuilder();
        $recursiveQuery
            ->union($basePart = $this->connection->createQueryBuilder())
            ->addUnion($recursivePart = $this->connection->createQueryBuilder(), $type);

        $this->options = new CompositeQueryOptions(recursive: true);
        $this->addSubQuery(
            $name,
            $recursiveQuery,
        );

        return [$basePart, $recursivePart];
    }

    /**
     * @param non-empty-string $withAlias
     * @param non-empty-string $fromAlias
     *
     * @return JoinInfo
     */
    public function joinOntoCte(string $withAlias, string $fromAlias, Column $column, ?Column $rightColumn = null): array
    {
        $rightColumn = $rightColumn ?? $column;

        return [
            'fromAlias' => $fromAlias,
            'join' => $withAlias,
            'alias' => $withAlias,
            'condition' => Comparison::equal(
                $column->from($fromAlias),
                $rightColumn->from($withAlias)
            )->toSQL(),
        ];
    }

    /**
     * This method can be used in situations where you have a table in which you have prefiltered matching data.
     * Conditions:
     * - Your main query searches for records in a table for record
     * - You already have a subquery that already pre-filtered a list of records that you are interested in.
     *
     * This function will:
     * - Add the subquery as a WITH statement
     * - Apply the pre-filtered set as a left join to the main query
     * - Return the joined column
     *
     * You can validate if there:
     * - is a match: $column IS NOT NULL
     * - is no match $column IS NULL
     *
     * @param non-empty-string $subQueryAlias
     * @param ?QueryBuilder    $targetQuery   can be used to specify a different join target than the main query
     */
    public function joinOnMatchingLookupTableRecords(
        string $subQueryAlias,
        QueryBuilder $subQuery,
        Column $joinColumn,
        ?QueryBuilder $targetQuery = null,
    ): Column {
        invariant(null !== $joinColumn->from, 'Table name must be set on security join column.');

        $this->addSubQuery($subQueryAlias, $subQuery);
        $targetQuery = $targetQuery ?? $this->mainQuery();
        $targetQuery->leftJoin(
            ...$this->joinOntoCte(
                $subQueryAlias,
                $joinColumn->from,
                $joinColumn
            )
        );

        return $this->cteColumn($subQueryAlias, $joinColumn->name);
    }

    /**
     * @param non-empty-string $withAlias
     * @param non-empty-string $name
     */
    public function cteColumn(string $withAlias, string $name): Column
    {
        return new Column($name, $withAlias);
    }

    public function execute(): Result
    {
        $params = $paramTypes = [];
        foreach ($this->with as [$query]) {
            $params = merge($params, $query->getParameters());
            $paramTypes = merge($paramTypes, $query->getParameterTypes());
        }

        return $this->connection->executeQuery(
            $this->toSQL(),
            merge($params, $this->query->getParameters()),
            merge($paramTypes, $this->query->getParameterTypes()),
        );
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        if (!$this->with) {
            /** @var non-empty-string */
            return $this->query->getSQL();
        }

        return sprintf(
            'WITH%s %s %s',
            $this->options->recursive ? ' RECURSIVE' : '',
            Str\join(
                map_with_key(
                    $this->with,
                    static fn (string $alias, array $query): string => sprintf(
                        '%s AS %s(%s)',
                        $alias,
                        match (true) {
                            $query[1]?->materialized ?? null === true => 'MATERIALIZED ',
                            $query[1]?->materialized ?? null === false => 'NOT MATERIALIZED ',
                            default => '',
                        },
                        $query[0]->getSQL()
                    )
                ),
                ', '
            ),
            $this->query->getSQL()
        );
    }

    /**
     * Immutably map over current main query.
     *
     * @param \Closure(QueryBuilder): QueryBuilder $modifier
     */
    public function map(\Closure $modifier): self
    {
        $new = clone $this;
        $new->query = $modifier($new->query);

        return $new;
    }

    /**
     * Make sure to clone internal query as well so that it won't be mutably altered after cloning.
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        $this->with = map(
            $this->with,
            fn (array $query): array => [
                clone $query[0],
                $query[1] ?? null,
            ],
        );
    }
}
