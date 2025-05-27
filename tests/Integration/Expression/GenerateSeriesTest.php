<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Alias;
use Phpro\DbalTools\Expression\Cast;
use Phpro\DbalTools\Expression\GenerateSeries;
use Phpro\DbalTools\Expression\Interval;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class GenerateSeriesTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_build_simple_series(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select('*')->from(
            new GenerateSeries(
                SqlExpression::int(3),
                SqlExpression::int(6),
            )->toSQL()
        );

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                [3],
                [4],
                [5],
                [6],
            ],
            $actualResults
        );
    }

    #[Test]
    public function it_can_build_series_with_step(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select('*')->from(
            new GenerateSeries(
                SqlExpression::int(3),
                SqlExpression::int(6),
                SqlExpression::int(2),
            )->toSQL()
        );

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                [3],
                [5],
            ],
            $actualResults
        );
    }

    #[Test]
    public function it_can_build_date_series(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb
            ->select(
                Cast::date(new Column('dates', null))->toSQL()
            )->from(
                new Alias(
                    new GenerateSeries(
                        Cast::date(new LiteralString('2025-05-27')),
                        Cast::date(new LiteralString('2025-05-30')),
                        Interval::days(1),
                    ),
                    'dates'
                )->toSQL()
            );

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                ['2025-05-27'],
                ['2025-05-28'],
                ['2025-05-29'],
                ['2025-05-30'],
            ],
            $actualResults
        );
    }

    #[Test]
    public function it_can_build_datetime_series_with_timezone(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb
            ->select(
                new Column('dates', null)->toSQL()
            )->from(
                new Alias(
                    new GenerateSeries(
                        new LiteralString('2025-10-23 03:00:00+02'),
                        new LiteralString('2025-10-28 03:00:00+02'),
                        Interval::days(1),
                        new LiteralString('Europe/Brussels'),
                    ),
                    'dates',
                )->toSQL()
            );

        $actualResults = $qb->fetchAllNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(
            [
                ['2025-10-23 01:00:00+00'],
                ['2025-10-24 01:00:00+00'],
                ['2025-10-25 01:00:00+00'],
                ['2025-10-26 02:00:00+00'],
                ['2025-10-27 02:00:00+00'],
            ],
            $actualResults
        );
    }
}
