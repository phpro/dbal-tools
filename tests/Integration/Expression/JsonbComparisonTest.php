<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\From;
use Phpro\DbalTools\Expression\JsonbComparison;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class JsonbComparisonTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_check_if_the_jsonb_field_contains_any_of_the_provided_keys(): void
    {
        $from = new From(
            new SqlExpression(
                "(VALUES 
            ('476f9772-4536-4ab8-b6a8-2422a32d44f9', '[".'"ROLE_USER", "ROLE_AUTOMATIONS"'."]'::jsonb),
            ('f6bec399-a1ab-464c-9d59-427379179011', '[".'"ROLE_ADMIN"'."]'::jsonb),
            ('d7edeadb-b9d4-45e8-b3dd-dcbfdcbaf931', '[".'"ROLE_USER"'."]'::jsonb)
        ) AS foo(id, roles)"
            )
        );

        $where = JsonbComparison::containsAnyOfKeys(
            new Column('roles', 'foo'),
            new SqlExpression("array ['ROLE_USER', 'ROLE_AUTOMATIONS']")
        );

        $sql = <<<EOSQL
            SELECT foo.id
            {$from->toSQL()}
            WHERE {$where->toSQL()}
        EOSQL;

        $result = self::connection()->executeQuery($sql);

        self::assertSame(
            [
                ['id' => '476f9772-4536-4ab8-b6a8-2422a32d44f9'],
                ['id' => 'd7edeadb-b9d4-45e8-b3dd-dcbfdcbaf931'],
            ],
            $result->fetchAllAssociative(),
        );
    }
}
