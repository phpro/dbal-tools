<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Expression\ToJsonb;
use Phpro\DbalTools\Expression\Vector;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;
use function Psl\Json\encode;

final class ToJsonbTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_build_json(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            new ToJsonb(new Vector(SqlExpression::int(132)))->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertJsonStringEqualsJsonString($actualResults[0], encode([132]));
    }
}
