<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Interval;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class IntervalTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_be_used_to_calculate_date_offsets(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select('(NOW() + '.Interval::days(1)->toSQL().')::DATE');

        $actualResults = $qb->fetchNumeric();
        self::assertSame(date('Y-m-d', strtotime('+1 day')), $actualResults[0]);
    }

    #[Test]
    public function it_can_evaluate_interval_literally(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(Interval::literal('2 years')->toSQL());

        $actualResults = $qb->fetchNumeric();
        self::assertSame('2 years', $actualResults[0]);
    }

    #[Test]
    public function it_can_evaluate_interval_in_days(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(Interval::days(2)->toSQL());

        $actualResults = $qb->fetchNumeric();
        self::assertSame('2 days', $actualResults[0]);
    }
}
