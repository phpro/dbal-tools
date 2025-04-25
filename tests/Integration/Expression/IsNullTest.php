<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Expression\IsNull;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class IsNullTest extends DbalReaderTestCase
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
    public function it_can_evaluate_null(Expression $expr, bool $expectedResult): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new IsNull($expr))->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expectedResult, $actualResults[0]);
    }

    public static function provideTestCases(): \Generator
    {
        yield 'null' => [SqlExpression::null(), true];
        yield 'not_null' => [new LiteralString('2'), false];
    }
}
