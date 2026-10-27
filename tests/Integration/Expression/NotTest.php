<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Expression\Not;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class NotTest extends DbalReaderTestCase
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
    public function it_can_negate_an_expression(Expression $expr, ?bool $expectedResult): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select((new Not($expr))->toSQL());

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expectedResult, $actualResults[0]);
    }

    public static function provideTestCases(): \Generator
    {
        yield 'true' => [SqlExpression::true(), false];
        yield 'false' => [SqlExpression::false(), true];
        yield 'null' => [new SqlExpression('NULL::boolean'), null];
        yield 'negates_the_whole_expression' => [new SqlExpression('false OR true'), false];
    }
}
