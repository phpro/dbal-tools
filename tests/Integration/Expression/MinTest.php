<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Min;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class MinTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_evaluate_min(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(new Min(new Column('countable', 'foo'))->toSQL())
            ->from(
                '(VALUES (1), (2), (3))',
                'foo (countable)'
            );

        $actualResults = $qb->fetchAllNumeric();
        self::assertCount(1, $actualResults);
        self::assertSame(1, $actualResults[0][0]);
    }
}
