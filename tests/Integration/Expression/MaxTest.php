<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Max;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class MaxTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    #[DataProvider('maxDataProvider')]
    public function it_can_evaluate_max($expression, $expectedResult): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new Max($expression))->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expectedResult, $actualResults[0]);
    }

    public static function maxDataProvider(): \Generator
    {
        yield 'single_value' => [SqlExpression::parameter('1'), 1];
        yield 'multiple_values' => [SqlExpression::parameter('2'), 2];
        yield 'null_value' => [SqlExpression::null(), null];
    }
}
