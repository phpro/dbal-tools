<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\JsonBuildObject;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

use function Psl\Json\decode;

final class JsonBuildObjectTest extends DbalReaderTestCase
{
    protected static function schemaTables(): array
    {
        return [];
    }

    protected function createFixtures(): void
    {
    }

    #[Test]
    public function it_builds_json_in_the_order_of_the_shape(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new JsonBuildObject([
                'b' => new LiteralString('1'),
                'a' => new SqlExpression('1'),
            ]))->toSQL(),
            JsonBuildObject::nullable(SqlExpression::null(), [
                'a' => new SqlExpression('1'),
            ])->toSQL(),
            JsonBuildObject::nullable(SqlExpression::true(), [
                'a' => new SqlExpression('1'),
            ])->toSQL(),
            JsonBuildObject::empty()->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame(['b' => '1', 'a' => 1], decode($actualResults[0]));
        self::assertNull($actualResults[1]);
        self::assertSame(['a' => 1], decode($actualResults[2]));
        self::assertSame([], decode($actualResults[3]));
    }
}
