<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Expression\Sum;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class SumTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {

    }

    #[Test]
    public function it_can_evaluate_sum(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new Sum(new SqlExpression('countable')))->toSQL()
        )->from(
            '(VALUES (1), (2), (3))',
            'foo (countable)'
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(6, $actualResults[0]);
    }
}
