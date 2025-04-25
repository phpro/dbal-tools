<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Pagerfanta;

use Doctrine\DBAL\Query\QueryBuilder;
use Pagerfanta\Doctrine\DBAL\QueryAdapter;
use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Pagerfanta\QueryAdapterFactory;
use Phpro\DbalTools\Test\DbalReaderTestCase;

class QueryAdapterFactoryTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    public function test_it_can_execute_composed_queries(): void
    {
        $query = self::connection()->createQueryBuilder();

        $query
            ->select('memory.foo')
            ->from('(VALUES (1), (2), (3))', 'memory (foo)')
            ->groupBy('memory.foo');

        $adapter = self::QueryAdapterFactory($query, new Column('foo', 'memory'));
        self::assertSame(3, $adapter->getNbResults());
        self::assertSame([['foo' => 1]], $adapter->getSlice(0, 1));
        self::assertSame([['foo' => 2]], $adapter->getSlice(1, 1));
        self::assertSame([['foo' => 3]], $adapter->getSlice(2, 1));
        self::assertSame([['foo' => 1], ['foo' => 2]], $adapter->getSlice(0, 2));
        self::assertSame([['foo' => 2], ['foo' => 3]], $adapter->getSlice(1, 2));
        self::assertSame([['foo' => 3]], $adapter->getSlice(2, 2));
    }

    public static function QueryAdapterFactory(QueryBuilder $query, Column $countColumn): QueryAdapter
    {
        return QueryAdapterFactory::create($query, $countColumn);
    }
}
