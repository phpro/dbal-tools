<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Cast;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class CastTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_cast(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            Cast::varChar(
                new SqlExpression('1')
            )->toSQL(),
            Cast::decimal(
                new SqlExpression('1.23')
            )->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], '1');
        self::assertSame($actualResults[1], '1.23');
    }
}
