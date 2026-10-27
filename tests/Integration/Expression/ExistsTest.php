<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Exists;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class ExistsTest extends DbalReaderTestCase
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
    public function it_can_evaluate_whether_a_subquery_returns_rows(string $condition, bool $expectedResult): void
    {
        $subQuery = $this->connection()->createQueryBuilder()
            ->select('1')
            ->from('(VALUES (1), (2))', 'numbers (number)')
            ->where($condition);

        $qb = $this->connection()->createQueryBuilder();
        $qb->select((new Exists($subQuery))->toSQL());

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($expectedResult, $actualResults[0]);
    }

    public static function provideTestCases(): \Generator
    {
        yield 'rows' => ['numbers.number = 2', true];
        yield 'no_rows' => ['numbers.number = 3', false];
    }
}
