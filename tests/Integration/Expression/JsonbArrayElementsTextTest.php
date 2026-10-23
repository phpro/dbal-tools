<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\Cast;
use Phpro\DbalTools\Expression\JsonbArrayElementsText;
use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class JsonbArrayElementsTextTest extends DbalReaderTestCase
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
            'jsonb_array_elements_text(data)',
            new JsonbArrayElementsText(new SqlExpression('data'))->toSQL()
        );
    }

    #[Test]
    public function it_returns_a_row_per_array_element(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            new JsonbArrayElementsText(Cast::jsonb(new LiteralString('["a", "b"]')))->toSQL()
        );

        self::assertSame(['a', 'b'], $qb->fetchFirstColumn());
    }
}
