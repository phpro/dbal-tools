<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\OrderBy;
use Phpro\DbalTools\Expression\Over;
use Phpro\DbalTools\Expression\PartitionBy;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Expression\Sum;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class OverTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_perform_aggregation_over_full_window(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            'character',
            Over::aggregation(
                new Sum(new Column('total', from: null)),
                Over::fullWindow()
            )->toSQL(),
        )->from(
            '(VALUES (\'a\', 2), (\'a\', 1), (\'b\', 1), (\'c\', 3))',
            'foo (character, total)'
        );

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                ['a', 7],
                ['a', 7],
                ['b', 7],
                ['c', 7],
            ],
            $actualResults
        );
    }

    #[Test]
    public function it_can_perform_aggregation_over_partition(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            'character',
            Over::aggregation(
                new Sum(new Column('total', from: null)),
                Over::partition(
                    new PartitionBy(new Column('character', from: null)),
                ),
            )->toSQL(),
        )->from(
            '(VALUES (\'a\', 2), (\'a\', 1), (\'b\', 1), (\'c\', 3))',
            'foo (character, total)'
        );

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                ['a', 3],
                ['a', 3],
                ['b', 1],
                ['c', 3],
            ],
            $actualResults
        );
    }

    #[Test]
    public function it_can_perform_partition_over_partition_with_order_by(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            'character',
            Over::aggregation(
                new SqlExpression('RANK()'),
                Over::partition(
                    new PartitionBy(new Column('character', from: null)),
                    new OrderBy(
                        OrderBy::field(new Column('total', from: null))
                    ),
                ),
            )->toSQL(),
        )->from(
            '(VALUES (1, \'a\', 2), (2, \'a\', 1), (3, \'b\', 1), (4, \'c\', 3))',
            'foo (id, character, total)'
        )->orderBy('id');

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                ['a', 2],
                ['a', 1],
                ['b', 1],
                ['c', 1],
            ],
            $actualResults
        );
    }

    #[Test]
    public function it_can_perform_partition_with_order_by(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            'character',
            Over::aggregation(
                new SqlExpression('RANK()'),
                Over::orderBy(
                    new OrderBy(
                        OrderBy::field(new Column('total', from: null))
                    ),
                ),
            )->toSQL(),
        )->from(
            '(VALUES (1, \'a\', 2), (2, \'a\', 1), (3, \'b\', 1), (4, \'c\', 3))',
            'foo (id, character, total)'
        )->orderBy('id');

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                ['a', 3],
                ['a', 1],
                ['b', 1],
                ['c', 4],
            ],
            $actualResults
        );
    }
}
