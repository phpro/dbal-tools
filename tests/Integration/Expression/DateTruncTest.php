<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\DateTrunc;
use Phpro\DbalTools\Expression\DateTruncField;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class DateTruncTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_truncate_to_a_specific_place_of_a_date(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(new DateTrunc(DateTruncField::Year, new SqlExpression('NOW()'))->toSQL().'::DATE');

        $actualResults = $qb->fetchNumeric();
        self::assertSame(date('Y').'-01-01', $actualResults[0]);
    }
}
