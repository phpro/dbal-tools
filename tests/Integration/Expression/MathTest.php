<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Math;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class MathTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_perform_math_operations(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Math::add(
                SqlExpression::int(1),
                SqlExpression::int(2)
            )->toSQL(),
            Math::subtract(
                SqlExpression::int(5),
                SqlExpression::int(2)
            )->toSQL(),
            Math::multiply(
                SqlExpression::int(2),
                SqlExpression::int(3)
            )->toSQL(),
            Math::divide(
                SqlExpression::int(6),
                SqlExpression::int(2)
            )->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], 3);
        self::assertSame($actualResults[1], 3);
        self::assertSame($actualResults[2], 6);
        self::assertSame($actualResults[3], 3);
    }

    #[Test]
    public function it_knows_order_of_operations(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Math::add(
                Math::multiply(
                    SqlExpression::int(5),
                    SqlExpression::int(2)
                ),
                SqlExpression::int(2)
            )->toSQL(),
            Math::multiply(
                Math::add(
                    SqlExpression::int(5),
                    SqlExpression::int(2)
                ),
                SqlExpression::int(2)
            )->toSQL(),
            Math::add(
                SqlExpression::int(5),
                Math::subtract(
                    SqlExpression::int(10),
                    SqlExpression::int(2)
                )
            )->toSQL(),
            Math::add(
                SqlExpression::int(5),
                Math::divide(
                    SqlExpression::int(10),
                    SqlExpression::int(2)
                )
            )->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], 12);
        self::assertSame($actualResults[1], 14);
        self::assertSame($actualResults[2], 13);
        self::assertSame($actualResults[3], 10);
    }

    #[Test]
    public function it_can_work_with_decimals(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Math::add(
                new SqlExpression('1.25'),
                new SqlExpression('2.50')
            )->toSQL(),
            Math::add(
                new SqlExpression('1.50'),
                new SqlExpression('2.50')
            )->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], '3.75');
        self::assertSame($actualResults[1], '4.00');
    }
}
