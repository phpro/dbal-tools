<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\CaseInsensitiveComparison;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class CaseInsensitiveComparisonTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_perform_case_insensitive_equal_comparison(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            CaseInsensitiveComparison::equal(
                new LiteralString('HELLO'),
                new LiteralString('hello')
            )->toSQL(),
            CaseInsensitiveComparison::equal(
                new LiteralString('Hello'),
                new LiteralString('HELLO')
            )->toSQL(),
            CaseInsensitiveComparison::equal(
                new LiteralString('HELLO'),
                new LiteralString('world')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(true, $actualResults[0]);
        self::assertSame(true, $actualResults[1]);
        self::assertSame(false, $actualResults[2]);
    }

    #[Test]
    public function it_can_perform_case_insensitive_not_equal_comparison(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            CaseInsensitiveComparison::notEqual(
                new LiteralString('HELLO'),
                new LiteralString('hello')
            )->toSQL(),
            CaseInsensitiveComparison::notEqual(
                new LiteralString('HELLO'),
                new LiteralString('world')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(false, $actualResults[0]);
        self::assertSame(true, $actualResults[1]);
    }

    #[Test]
    public function it_can_perform_case_insensitive_greater_than_comparison(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            CaseInsensitiveComparison::greaterThan(
                new LiteralString('ZEBRA'),
                new LiteralString('apple')
            )->toSQL(),
            CaseInsensitiveComparison::greaterThan(
                new LiteralString('Apple'),
                new LiteralString('ZEBRA')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(true, $actualResults[0]);
        self::assertSame(false, $actualResults[1]);
    }

    #[Test]
    public function it_can_perform_case_insensitive_less_than_comparison(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            CaseInsensitiveComparison::lessThan(
                new LiteralString('APPLE'),
                new LiteralString('zebra')
            )->toSQL(),
            CaseInsensitiveComparison::lessThan(
                new LiteralString('Zebra'),
                new LiteralString('APPLE')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(true, $actualResults[0]);
        self::assertSame(false, $actualResults[1]);
    }

    #[Test]
    public function it_can_perform_case_insensitive_greater_than_or_equal_comparison(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            CaseInsensitiveComparison::greaterThanOrEqual(
                new LiteralString('ZEBRA'),
                new LiteralString('apple')
            )->toSQL(),
            CaseInsensitiveComparison::greaterThanOrEqual(
                new LiteralString('Apple'),
                new LiteralString('APPLE')
            )->toSQL(),
            CaseInsensitiveComparison::greaterThanOrEqual(
                new LiteralString('Apple'),
                new LiteralString('ZEBRA')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(true, $actualResults[0]);
        self::assertSame(true, $actualResults[1]);
        self::assertSame(false, $actualResults[2]);
    }

    #[Test]
    public function it_can_perform_case_insensitive_less_than_or_equal_comparison(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            CaseInsensitiveComparison::lessThanOrEqual(
                new LiteralString('APPLE'),
                new LiteralString('zebra')
            )->toSQL(),
            CaseInsensitiveComparison::lessThanOrEqual(
                new LiteralString('Apple'),
                new LiteralString('APPLE')
            )->toSQL(),
            CaseInsensitiveComparison::lessThanOrEqual(
                new LiteralString('Zebra'),
                new LiteralString('APPLE')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(true, $actualResults[0]);
        self::assertSame(true, $actualResults[1]);
        self::assertSame(false, $actualResults[2]);
    }

    #[Test]
    public function it_can_perform_case_insensitive_comparison_with_numbers(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            CaseInsensitiveComparison::equal(
                new LiteralString('123'),
                new LiteralString('123')
            )->toSQL(),
            CaseInsensitiveComparison::notEqual(
                new LiteralString('123'),
                new LiteralString('456')
            )->toSQL(),
            CaseInsensitiveComparison::greaterThan(
                new LiteralString('456'),
                new LiteralString('123')
            )->toSQL(),
            CaseInsensitiveComparison::lessThan(
                new LiteralString('123'),
                new LiteralString('456')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(true, $actualResults[0]);
        self::assertSame(true, $actualResults[1]);
        self::assertSame(true, $actualResults[2]);
        self::assertSame(true, $actualResults[3]);
    }

    #[Test]
    public function it_generates_correct_sql_with_lower_functions(): void
    {
        $comparison = CaseInsensitiveComparison::equal(
            new LiteralString('HELLO'),
            new LiteralString('world')
        );

        $sql = $comparison->toSQL();

        self::assertStringContainsString('LOWER(', $sql);
        self::assertStringContainsString('=', $sql);

        self::assertSame(2, mb_substr_count($sql, 'LOWER('));
    }
}
