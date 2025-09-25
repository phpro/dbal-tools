<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\JsonbBuildArray;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;
use function Psl\Json\encode;

final class JsonbBuildArrayTest extends DbalReaderTestCase
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
            (new JsonbBuildArray([
                new SqlExpression('1'),
                new LiteralString('1'),
            ]))->toSQL(),
            JsonbBuildArray::empty()->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertJsonStringEqualsJsonString($actualResults[0], encode([1, '1']));
        self::assertJsonStringEqualsJsonString($actualResults[1], encode([]));
    }
}
