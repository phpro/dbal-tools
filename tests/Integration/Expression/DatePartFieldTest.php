<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\DatePart;
use Phpro\DbalTools\Expression\DatePartField;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class DatePartFieldTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_find_specific_parts_of_a_date(): void
    {
        foreach (DatePartField::cases() as $case) {
            $qb = $this->connection()->createQueryBuilder();
            $qb->select(new DatePart($case, new SqlExpression('NOW()'))->toSQL());

            $actualResults = $qb->fetchNumeric();
            self::assertIsFloat($actualResults[0]);
        }
    }
}
