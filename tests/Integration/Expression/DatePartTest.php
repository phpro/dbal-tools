<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\DatePart;
use Phpro\DbalTools\Expression\DatePartField;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class DatePartTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_find_a_part_of_a_date(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(new DatePart(DatePartField::Year, new SqlExpression('NOW()'))->toSQL());

        $actualResults = $qb->fetchNumeric();
        self::assertSame((float) date('Y'), $actualResults[0]);
    }
}
