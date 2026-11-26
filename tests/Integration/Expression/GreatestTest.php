<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Greatest;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class GreatestTest extends DbalReaderTestCase
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
    public function it_can_select_the_greatest_value(?int $expectedResult, array $expressions): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new Greatest(...$expressions))->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expectedResult, $actualResults[0]);
    }

    public static function provideTestCases(): \Generator
    {
        yield 'two_values' => [
            'expectedResult' => 3,
            'expressions' => [SqlExpression::int(1), SqlExpression::int(3)],
        ];

        yield 'multiple_values' => [
            'expectedResult' => 3,
            'expressions' => [SqlExpression::int(2), SqlExpression::int(3), SqlExpression::int(1)],
        ];

        yield 'null_is_ignored' => [
            'expectedResult' => 2,
            'expressions' => [SqlExpression::null(), SqlExpression::int(2)],
        ];

        yield 'only_nulls' => [
            'expectedResult' => null,
            'expressions' => [SqlExpression::null(), SqlExpression::null()],
        ];
    }
}
