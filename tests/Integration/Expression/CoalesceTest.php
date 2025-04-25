<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Coalesce;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class CoalesceTest extends DbalReaderTestCase
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
    public function it_can_coalesce(bool $expectedResult, array $expressions): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new Coalesce(...$expressions))->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expectedResult, $actualResults[0]);
    }

    public static function provideTestCases(): \Generator
    {
        yield 'false_true' => [
            'expectedResult' => false,
            'expressions' => [SqlExpression::false(), SqlExpression::true()],
        ];

        yield 'null_true' => [
            'expectedResult' => true,
            'expressions' => [SqlExpression::null(), SqlExpression::true()],
        ];

        yield 'null_false' => [
            'expectedResult' => false,
            'expressions' => [SqlExpression::null(), SqlExpression::false()],
        ];

        yield 'multiple_expressions' => [
            'expectedResult' => true,
            'expressions' => [SqlExpression::null(), SqlExpression::null(), SqlExpression::true()],
        ];
    }
}
