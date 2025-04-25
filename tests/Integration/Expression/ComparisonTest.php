<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Comparison;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class ComparisonTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_perform_equal_comparison_checks(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Comparison::equal(
                new SqlExpression('1'),
                new SqlExpression('1')
            )->toSQL(),
            Comparison::equal(
                new SqlExpression('1'),
                new SqlExpression('2')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], true);
        self::assertSame($actualResults[1], false);
    }

    #[Test]
    public function it_can_perform_not_equal_comparison_checks(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Comparison::notEqual(
                new SqlExpression('1'),
                new SqlExpression('1')
            )->toSQL(),
            Comparison::notEqual(
                new SqlExpression('1'),
                new SqlExpression('2')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], false);
        self::assertSame($actualResults[1], true);
    }

    #[Test]
    public function it_can_perform_greater_than_comparison_checks(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Comparison::greaterThan(
                new SqlExpression('1'),
                new SqlExpression('2')
            )->toSQL(),
            Comparison::greaterThan(
                new SqlExpression('1'),
                new SqlExpression('1')
            )->toSQL(),
            Comparison::greaterThan(
                new SqlExpression('2'),
                new SqlExpression('1')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], false);
        self::assertSame($actualResults[1], false);
        self::assertSame($actualResults[2], true);
    }

    #[Test]
    public function it_can_perform_greater_than_or_equal_comparison_checks(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Comparison::greaterThanOrEqual(
                new SqlExpression('1'),
                new SqlExpression('2')
            )->toSQL(),
            Comparison::greaterThanOrEqual(
                new SqlExpression('1'),
                new SqlExpression('1')
            )->toSQL(),
            Comparison::greaterThanOrEqual(
                new SqlExpression('2'),
                new SqlExpression('1')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], false);
        self::assertSame($actualResults[1], true);
        self::assertSame($actualResults[2], true);
    }

    #[Test]
    public function it_can_perform_less_than_comparison_checks(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Comparison::lessThan(
                new SqlExpression('1'),
                new SqlExpression('2')
            )->toSQL(),
            Comparison::lessThan(
                new SqlExpression('1'),
                new SqlExpression('1')
            )->toSQL(),
            Comparison::lessThan(
                new SqlExpression('2'),
                new SqlExpression('1')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], true);
        self::assertSame($actualResults[1], false);
        self::assertSame($actualResults[2], false);
    }

    #[Test]
    public function it_can_perform_less_than_or_equal_comparison_checks(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Comparison::lessThanOrEqual(
                new SqlExpression('1'),
                new SqlExpression('2')
            )->toSQL(),
            Comparison::lessThanOrEqual(
                new SqlExpression('1'),
                new SqlExpression('1')
            )->toSQL(),
            Comparison::lessThanOrEqual(
                new SqlExpression('2'),
                new SqlExpression('1')
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], true);
        self::assertSame($actualResults[1], true);
        self::assertSame($actualResults[2], false);
    }
}
