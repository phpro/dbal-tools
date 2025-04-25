<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Pagerfanta\Adapter;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Pagerfanta\Adapter\CompositeDbalQueryAdapter;
use Phpro\DbalTools\Query\CompositeQuery;
use Phpro\DbalTools\Test\DbalReaderTestCase;

class CompositeDbalQueryAdapterTest extends DbalReaderTestCase
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
        $compositeQuery = CompositeQuery::from(self::connection());
        $compositeQuery->mainQuery()
            ->select('linked.foo')
            ->from('linked');

        $compositeQuery->createSubQuery('linked')
            ->select('memory.foo')
            ->from('(VALUES (1), (2), (3))', 'memory (foo)');

        $adapter = self::getCompositeDbalQueryAdapter($compositeQuery, $compositeQuery->cteColumn('linked', 'foo'));

        self::assertSame(3, $adapter->getNbResults());
        self::assertSame([['foo' => 1]], iterator_to_array($adapter->getSlice(0, 1)));
        self::assertSame([['foo' => 2]], iterator_to_array($adapter->getSlice(1, 1)));
        self::assertSame([['foo' => 3]], iterator_to_array($adapter->getSlice(2, 1)));
        self::assertSame([['foo' => 1], ['foo' => 2]], iterator_to_array($adapter->getSlice(0, 2)));
        self::assertSame([['foo' => 2], ['foo' => 3]], iterator_to_array($adapter->getSlice(1, 2)));
        self::assertSame([['foo' => 3]], iterator_to_array($adapter->getSlice(2, 2)));
    }

    public static function getCompositeDbalQueryAdapter(CompositeQuery $query, Column $countColumn): CompositeDbalQueryAdapter
    {
        return CompositeDbalQueryAdapter::default($query, $countColumn);
    }
}
