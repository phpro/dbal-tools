<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\JsonbBuildObject;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;
use function Psl\Json\encode;

final class JsonbBuildObjectTest extends DbalReaderTestCase
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
            (new JsonbBuildObject([
                'a' => new SqlExpression('1'),
                'b' => new LiteralString('1'),
            ]))->toSQL(),
            JsonbBuildObject::nullable(SqlExpression::null(), [
                'a' => new SqlExpression('1'),
            ])->toSQL(),
            JsonbBuildObject::nullable(SqlExpression::true(), [
                'a' => new SqlExpression('1'),
            ])->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertJsonStringEqualsJsonString($actualResults[0], encode(['a' => 1, 'b' => '1']));
        self::assertNull($actualResults[1]);
        self::assertJsonStringEqualsJsonString($actualResults[2], encode(['a' => 1]));
    }
}
