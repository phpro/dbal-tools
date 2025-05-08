<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\DateTrunc;
use Phpro\DbalTools\Expression\DateTruncField;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class DateTruncFieldTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_truncate_to_a_specific_part_of_a_date(): void
    {
        foreach (DateTruncField::cases() as $case) {
            $qb = $this->connection()->createQueryBuilder();
            $qb->select(new DateTrunc($case, new SqlExpression('NOW()'))->toSQL());

            $actualResults = $qb->fetchNumeric();
            self::assertNotEmpty($actualResults[0]);
        }
    }
}
