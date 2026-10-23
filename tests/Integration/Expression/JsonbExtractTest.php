<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Cast;
use Phpro\DbalTools\Expression\JsonbExtract;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class JsonbExtractTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_renders_sql(): void
    {
        self::assertSame(
            "data -> 'key'",
            JsonbExtract::field(new SqlExpression('data'), 'key')->toSQL()
        );
    }

    #[Test]
    public function it_renders_sql_as_text(): void
    {
        self::assertSame(
            "data ->> 'key'",
            JsonbExtract::text(new SqlExpression('data'), 'key')->toSQL()
        );
    }

    #[Test]
    public function it_escapes_the_key_as_a_string_literal(): void
    {
        self::assertSame(
            "data -> 'it''s'",
            JsonbExtract::field(new SqlExpression('data'), "it's")->toSQL()
        );
    }

    #[Test]
    public function it_can_get_fields(): void
    {
        $json = Cast::jsonb(new LiteralString('{"name": "Jos", "it\'s": [1, 2]}'));

        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            JsonbExtract::field($json, 'name')->toSQL(),
            JsonbExtract::text($json, 'name')->toSQL(),
            JsonbExtract::field($json, "it's")->toSQL(),
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame('"Jos"', $actualResults[0]);
        self::assertSame('Jos', $actualResults[1]);
        self::assertSame('[1, 2]', $actualResults[2]);
    }
}
