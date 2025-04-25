<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Composite;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class CompositeTest extends DbalReaderTestCase
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
    public function it_can_compose_expressions(bool $expectedResult, string $operator, array $expressions): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select((match ($operator) {
            'AND' => Composite::and(...$expressions),
            'OR' => Composite::or(...$expressions),
        })->toSQL());

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expectedResult, $actualResults[0]);
    }

    public static function provideTestCases(): \Generator
    {
        yield 'and_false' => [
            'expectedResult' => false,
            'operator' => 'AND',
            'expressions' => [SqlExpression::false()],
        ];

        yield 'and_true' => [
            'expectedResult' => true,
            'operator' => 'AND',
            'expressions' => [SqlExpression::true()],
        ];

        yield 'and_false_true' => [
            'expectedResult' => false,
            'operator' => 'AND',
            'expressions' => [SqlExpression::false(), SqlExpression::true()],
        ];

        yield 'and_true_true' => [
            'expectedResult' => true,
            'operator' => 'AND',
            'expressions' => [SqlExpression::true(), SqlExpression::true()],
        ];

        yield 'or_false' => [
            'expectedResult' => false,
            'operator' => 'OR',
            'expressions' => [SqlExpression::false()],
        ];

        yield 'or_true' => [
            'expectedResult' => true,
            'operator' => 'OR',
            'expressions' => [SqlExpression::true()],
        ];

        yield 'or_false_true' => [
            'expectedResult' => true,
            'operator' => 'OR',
            'expressions' => [SqlExpression::false(), SqlExpression::true()],
        ];

        yield 'or_true_true' => [
            'expectedResult' => true,
            'operator' => 'OR',
            'expressions' => [SqlExpression::true(), SqlExpression::true()],
        ];

        yield 'nested' => [
            'expectedResult' => true,
            'operator' => 'AND',
            'expressions' => [
                Composite::or(SqlExpression::true(), SqlExpression::false()),
                Composite::or(SqlExpression::true(), SqlExpression::false()),
                Composite::and(SqlExpression::true(), SqlExpression::true()),
            ],
        ];
    }
}
