<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Comparison;
use Phpro\DbalTools\Expression\Count;
use Phpro\DbalTools\Expression\Filter;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Expression\Sum;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class FilterTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {
    }

    #[Test]
    public function it_filters_the_rows_any_aggregate_sees(): void
    {
        $value = new SqlExpression('v.n');
        $aboveOne = Comparison::greaterThan($value, SqlExpression::int(1));

        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new Filter(new Count($value), $aboveOne))->toSQL(),
            (new Filter(new Sum($value), $aboveOne))->toSQL(),
        )->from('(VALUES (1), (2), (3))', 'v(n)');

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame([2, 5], $actualResults);
    }
}
