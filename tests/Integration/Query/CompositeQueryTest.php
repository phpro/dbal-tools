<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Query;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\UnionType;
use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;
use Phpro\DbalTools\Expression\Expressions;
use Phpro\DbalTools\Expression\In;
use Phpro\DbalTools\Expression\IsNotNull;
use Phpro\DbalTools\Expression\IsNull;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Query\CompositeQuery;
use Phpro\DbalTools\Query\CompositeSubQueryOptions;
use Phpro\DbalTools\Schema\Table;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psl\Exception\InvariantViolationException;

final class CompositeQueryTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
        self::connection()->insert(CompositeTable::name(), ['id' => 1]);
        self::connection()->insert(CompositeTable::name(), ['id' => 2]);
        self::connection()->insert(CompositeTable::name(), ['id' => 3]);
    }

    protected static function schemaTables(): array
    {
        return [
            CompositeTable::class,
        ];
    }

    #[Test]
    public function it_can_execute_composed_queries(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $compositeQuery->mainQuery()
            ->select($compositeQuery->cteColumn('linked', 'foo')->select())
            ->from('linked');

        $compositeQuery->createSubQuery('linked', CompositeSubQueryOptions::default())
            ->select('memory.foo')
            ->from('(VALUES (1), (2), (3))', 'memory (foo)');

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['foo' => 1],
            ['foo' => 2],
            ['foo' => 3],
        ], $result);
    }

    #[Test]
    public function it_can_execute_composed_queries_from_constructor_with_query_builders(): void
    {
        $compositeQuery = new CompositeQuery(
            self::connection(),
            self::connection()->createQueryBuilder()
                ->select('linked.foo')
                ->from('linked'),
            [
                'linked' => self::connection()->createQueryBuilder()
                    ->select('memory.foo')
                    ->from('(VALUES (1), (2), (3))', 'memory (foo)'),
            ]
        );

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['foo' => 1],
            ['foo' => 2],
            ['foo' => 3],
        ], $result);
    }

    #[Test]
    public function it_can_execute_composed_queries_from_constructor_with_query_builders_and_settings(): void
    {
        $compositeQuery = new CompositeQuery(
            self::connection(),
            self::connection()->createQueryBuilder()
                ->select('linked.foo')
                ->from('linked'),
            [
                'linked' => [
                    self::connection()->createQueryBuilder()
                        ->select('memory.foo')
                        ->from('(VALUES (1), (2), (3))', 'memory (foo)'),
                    CompositeSubQueryOptions::default(),
                ],
            ]
        );

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['foo' => 1],
            ['foo' => 2],
            ['foo' => 3],
        ], $result);
    }

    #[Test]
    public function it_acts_as_a_regular_query_on_no_subqueries(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $compositeQuery->mainQuery()
            ->select('memory.foo')
            ->from('(VALUES (1), (2), (3))', 'memory (foo)');

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['foo' => 1],
            ['foo' => 2],
            ['foo' => 3],
        ], $result);
    }

    #[Test]
    public function it_knows_how_to_join_on_cte_field(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $compositeQuery->mainQuery()
            ->select('linked1.foo')
            ->from('linked1')
            ->innerJoin(
                ...$compositeQuery->joinOntoCte('linked2', 'linked1', new Column('foo', null))
            );

        $compositeQuery->createSubQuery('linked1')
            ->select('foo')
            ->from('(VALUES (1), (2), (3))', 'memory1 (foo)');
        $compositeQuery->createSubQuery('linked2')
            ->select('foo')
            ->from('(VALUES (1), (2))', 'memory2 (foo)');

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['foo' => 1],
            ['foo' => 2],
        ], $result);
    }

    #[Test]
    public function it_can_immutably_map_a_full_composite_query_with_all_subqueries(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $mainQuery = $compositeQuery->mainQuery()->select('1');
        $subQuery = $compositeQuery->createSubQuery('x')->select('2');

        $newCompositeQuery = $compositeQuery->map(
            static function (QueryBuilder $newBuilder) use ($mainQuery): QueryBuilder {
                self::assertNotSame($mainQuery, $newBuilder);

                return $newBuilder;
            }
        );

        self::assertNotSame($compositeQuery, $newCompositeQuery);
        self::assertNotSame($mainQuery, $newCompositeQuery->mainQuery());
        self::assertNotSame($subQuery, $newCompositeQuery->subQuery('x'));

        self::assertSame($mainQuery->getSQL(), $newCompositeQuery->mainQuery()->getSQL());
        self::assertSame($subQuery->getSQL(), $newCompositeQuery->subQuery('x')->getSQL());
    }

    #[Test]
    public function it_can_immutably_map_a_full_composite_query_with_all_subqueries_and_change_complete_main_query(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $mainQuery = $compositeQuery->mainQuery()->select('1');
        $expectedMainQuery = self::connection()->createQueryBuilder()->select('3');
        $subQuery = $compositeQuery->createSubQuery('x')->select('2');

        $newCompositeQuery = $compositeQuery->map(
            static function (QueryBuilder $newBuilder) use ($mainQuery, $expectedMainQuery): QueryBuilder {
                self::assertNotSame($mainQuery, $newBuilder);

                return $expectedMainQuery;
            }
        );

        self::assertNotSame($compositeQuery, $newCompositeQuery);
        self::assertNotSame($mainQuery, $newCompositeQuery->mainQuery());
        self::assertNotSame($subQuery, $newCompositeQuery->subQuery('x'));

        self::assertSame($expectedMainQuery->getSQL(), $newCompositeQuery->mainQuery()->getSQL());
        self::assertSame($subQuery->getSQL(), $newCompositeQuery->subQuery('x')->getSQL());
    }

    #[Test]
    public function it_can_grab_subquery(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $subQuery = $compositeQuery->createSubQuery('x')->select('2');

        self::assertSame($subQuery, $compositeQuery->subQuery('x'));

        $this->expectException(InvariantViolationException::class);
        $compositeQuery->subQuery('unknown');
    }

    #[Test]
    public function it_can_check_if_a_subquery_exists(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $compositeQuery->createSubQuery('x');

        self::assertTrue($compositeQuery->hasSubQuery('x'));
        self::assertFalse($compositeQuery->hasSubQuery('y'));
    }

    #[Test]
    public function it_can_move_main_query_to_subquery(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());

        $compositeQuery->mainQuery()
            ->select('memory.foo')
            ->from('(VALUES (1), (2))', 'memory (foo)');

        $newCompositeQuery = $compositeQuery->moveMainQueryToSubQuery('subquery1', CompositeSubQueryOptions::default());
        $newCompositeQuery->mainQuery()->select('memory.foo')
            ->from('(VALUES (1), (2), (3))', 'memory (foo)');

        self::assertNotSame($compositeQuery, $newCompositeQuery);
        self::assertSame(
            'SELECT memory.foo FROM (VALUES (1), (2)) memory (foo)',
            $newCompositeQuery->subQuery('subquery1')->getSQL()
        );
        self::assertSame(
            'WITH subquery1 AS (SELECT memory.foo FROM (VALUES (1), (2)) memory (foo)) SELECT memory.foo FROM (VALUES (1), (2), (3)) memory (foo)',
            $newCompositeQuery->toSQL()
        );
        self::assertSame([
            ['foo' => 1],
            ['foo' => 2],
            ['foo' => 3],
        ], $newCompositeQuery->execute()->fetchAllAssociative());
    }

    #[Test]
    public function it_can_join_on_matching_lookup_table_records(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $mainQuery = $compositeQuery->mainQuery();

        $mainQuery
            ->select(CompositeTableColumns::Id->select())
            ->from(CompositeTable::name());

        $mainQuery->andWhere(
            (new IsNotNull(
                $compositeQuery->joinOnMatchingLookupTableRecords(
                    'lookup',
                    self::connection()->createQueryBuilder()
                        ->select('lookup.id')
                        ->from('(VALUES (1), (2))', 'lookup (id)'),
                    CompositeTableColumns::Id->column(),
                )
            ))->toSQL()
        );

        self::assertSame([
            ['id' => 1],
            ['id' => 2],
        ], $compositeQuery->execute()->fetchAllAssociative());
    }

    #[Test]
    public function it_can_join_on_non_matching_lookup_table_records(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $mainQuery = $compositeQuery->mainQuery();

        $mainQuery
            ->select(CompositeTableColumns::Id->select())
            ->from(CompositeTable::name());

        $mainQuery->andWhere(
            (new IsNull(
                $compositeQuery->joinOnMatchingLookupTableRecords(
                    'lookup',
                    self::connection()->createQueryBuilder()
                        ->select('lookup.id')
                        ->from('(VALUES (1), (2))', 'lookup (id)'),
                    CompositeTableColumns::Id->column(),
                )
            ))->toSQL()
        );

        self::assertSame([
            ['id' => 3],
        ], $compositeQuery->execute()->fetchAllAssociative());
    }

    #[Test]
    public function it_can_join_on_matching_lookup_table_on_different_target_query(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $mainQuery = $compositeQuery->mainQuery();

        $subQuery = self::connection()->createQueryBuilder();
        $subQuery
            ->select(CompositeTableColumns::Id->select())
            ->from(CompositeTable::name())
            ->where(
                new In(
                    CompositeTableColumns::Id,
                    new Expressions(
                        SqlExpression::int(1),
                        SqlExpression::int(2),
                    )
                )->toSQL()
            )->andWhere(
                (new IsNotNull(
                    $compositeQuery->joinOnMatchingLookupTableRecords(
                        'lookup',
                        self::connection()->createQueryBuilder()
                            ->select('lookup.id')
                            ->from('(VALUES (1))', 'lookup (id)'),
                        CompositeTableColumns::Id->column(),
                        $subQuery,
                    )
                ))->toSQL()
            );
        $compositeQuery->addSubQuery('subquery', $subQuery, CompositeSubQueryOptions::default());

        $mainQuery
            ->select(CompositeTableColumns::Id->select())
            ->from(CompositeTable::name())
            ->innerJoin(...$compositeQuery->joinOntoCte(
                'subquery',
                CompositeTable::name(),
                CompositeTableColumns::Id->column(),
            ));

        self::assertSame([
            ['id' => 1],
        ], $compositeQuery->execute()->fetchAllAssociative());
    }

    #[Test]
    public function it_can_create_and_execute_recursive_cte_with_union_all(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());

        [$basePart, $recursivePart] = $compositeQuery->createRecursiveSubQuery('numbers');
        $basePart
            ->select('1 AS n, 0 AS depth');

        $recursivePart
            ->select('n AS n, depth + 1 AS depth')
            ->from('numbers')
            ->where('depth < 2');

        $compositeQuery->mainQuery()
            ->select('n')
            ->from('numbers');

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['n' => 1],
            ['n' => 1],
            ['n' => 1],
        ], $result);
    }

    #[Test]
    public function it_can_create_and_execute_recursive_cte_with_union_distinct(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());

        [$basePart, $recursivePart] = $compositeQuery->createRecursiveSubQuery('numbers', UnionType::DISTINCT);
        $basePart
            ->select('1 AS n');

        $recursivePart
            ->select('n')
            ->from('numbers');

        $compositeQuery->mainQuery()
            ->select('n')
            ->from('numbers');

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['n' => 1],
        ], $result);
    }

    #[Test]
    public function it_can_combine_regular_and_recursive_ctes(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());

        $compositeQuery->createSubQuery('regular')
            ->select('10 AS multiplier');

        [$basePart, $recursivePart] = $compositeQuery->createRecursiveSubQuery('numbers');
        $basePart->select('1 AS n');
        $recursivePart
            ->select('n + 1 AS n')
            ->from('numbers')
            ->where('n < 3');

        // Main query combines both with explicit join
        $compositeQuery->mainQuery()
            ->select('numbers.n * regular.multiplier AS result')
            ->from('numbers')
            ->innerJoin('numbers', 'regular', 'regular', '1=1');

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['result' => 10],
            ['result' => 20],
            ['result' => 30],
        ], $result);
    }

    #[Test]
    public function it_can_execute_composed_materialized_queries(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $compositeQuery->mainQuery()
            ->select($compositeQuery->cteColumn('linked', 'foo')->select())
            ->from('linked');

        $compositeQuery->createSubQuery('linked', new CompositeSubQueryOptions(materialized: true))
            ->select('memory.foo')
            ->from('(VALUES (1), (2), (3))', 'memory (foo)');

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['foo' => 1],
            ['foo' => 2],
            ['foo' => 3],
        ], $result);
    }

    #[Test]
    public function it_can_execute_composed_non_materialized_queries(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());
        $compositeQuery->mainQuery()
            ->select($compositeQuery->cteColumn('linked', 'foo')->select())
            ->from('linked');

        $compositeQuery->createSubQuery('linked', new CompositeSubQueryOptions(materialized: false))
            ->select('memory.foo')
            ->from('(VALUES (1), (2), (3))', 'memory (foo)');

        $result = $compositeQuery->execute()->fetchAllAssociative();

        self::assertSame([
            ['foo' => 1],
            ['foo' => 2],
            ['foo' => 3],
        ], $result);
    }

    #[Test]
    public function it_can_trigger_recursive_error(): void
    {
        $compositeQuery = CompositeQuery::from(self::connection());

        $compositeQuery->mainQuery()
            ->select('memory.foo')
            ->from('(VALUES (1), (2), (3))', 'memory (foo)')
            ->where('memory.foo IN (SELECT memory.foo FROM memory)');

        $this->expectException(Exception::class);
        $compositeQuery->execute();
    }
}

class CompositeTable extends Table
{
    public static function name(): string
    {
        return 'composite_expressions';
    }

    public static function columns(): Columns
    {
        return Columns::for(CompositeTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        return new DoctrineTable(self::name(), [
            new DoctrineColumn(CompositeTableColumns::Id->value, Type::getType(Types::INTEGER)),
        ]);
    }
}

enum CompositeTableColumns: string implements TableColumnsInterface
{
    use TableColumnsTrait;

    case Id = 'id';

    public function linkedTableClass(): string
    {
        return CompositeTable::class;
    }
}
