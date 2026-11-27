<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Expression\IsNotNull;
use Phpro\DbalTools\Expression\JsonAgg;
use Phpro\DbalTools\Expression\OrderBy;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

use function Psl\Json\decode;

final class JsonAggTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {
    }

    #[Test]
    #[DataProvider('provideTestCases')]
    public function it_can_aggregate_into_json(?array $expected, bool $ordered, ?Expression $filter): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $value = new SqlExpression('v.n');
        $orderBy = $ordered ? new OrderBy(OrderBy::field($value, OrderBy::ASC)) : null;
        $aggregate = null === $filter ? new JsonAgg($value, $orderBy) : JsonAgg::filtered($value, $filter, $orderBy);

        $qb->select($aggregate->toSQL())->from('(VALUES (2), (1), (NULL::int))', 'v(n)');

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expected, null === $actualResults[0] ? null : decode($actualResults[0]));
    }

    public static function provideTestCases(): \Generator
    {
        yield 'keeps_nulls' => [[2, 1, null], false, null];
        yield 'ordered' => [[1, 2, null], true, null];
        yield 'filtered' => [[1, 2], true, new IsNotNull(new SqlExpression('v.n'))];
        yield 'nothing_left_after_filter' => [null, false, SqlExpression::false()];
    }
}
