<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class SqlExpressionTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_use_sql_expression(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new SqlExpression('1 as a, \'2\' as b'))->toSQL(),
        );

        $actualResults = $qb->fetchAssociative();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults['a'], 1);
        self::assertSame($actualResults['b'], '2');
    }

    #[Test]
    public function it_can_use_sql_from_parameter(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            SqlExpression::parameter(
                $qb->createNamedParameter('foo')
            )->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults['0'], 'foo');
    }

    #[Test]
    public function it_can_use_null(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            SqlExpression::null()->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults['0'], null);
    }

    #[Test]
    public function it_can_use_true(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            SqlExpression::true()->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertTrue($actualResults['0']);
    }

    #[Test]
    public function it_can_use_false(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            SqlExpression::false()->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertFalse($actualResults['0']);
    }

    #[Test]
    public function it_can_use_parenthized(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            $parsed = SqlExpression::parenthesized(
                SqlExpression::true()->toSQL()
            )->toSQL()
        );

        self::assertSame('(TRUE)', $parsed);

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertTrue($actualResults['0']);
    }

    #[Test]
    public function it_can_use_int(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            SqlExpression::int(1)->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults['0'], 1);
    }

    #[Test]
    public function it_can_escape_placeholder(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(SqlExpression::escapePlaceholder('\'["a", "b"]\'::jsonb ?& array[\'a\']')->toSQL());

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults['0'], true);
    }
}
