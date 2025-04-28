<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Phpro\DbalTools\Expression\LiteralString;
use Phpro\DbalTools\Expression\Vector;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class VectorTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    #[Test]
    public function it_can_create_an_array_constructor(): void
    {
        $array = new Vector(
            new LiteralString('ROLE_AUTOMATIONS'),
            new LiteralString('ROLE_ADMIN'),
        );

        $sql = <<<EOSQL
            SELECT {$array->toSQL()}
        EOSQL;

        $result = self::connection()->executeQuery($sql);

        self::assertSame(
            [
                ['array' => '{ROLE_AUTOMATIONS,ROLE_ADMIN}'],
            ],
            $result->fetchAllAssociative(),
        );
    }

    #[Test]
    public function it_can_create_a_nested_array_constructor(): void
    {
        $array = new Vector(
            new Vector(new LiteralString('ROLE_USER'), new LiteralString('ROLE_ADMIN')),
            new Vector(new LiteralString('ROLE_AUTOMATIONS'), new LiteralString('ROLE_ADMIN')),
        );

        $sql = <<<EOSQL
            SELECT {$array->toSQL()}
        EOSQL;

        $result = self::connection()->executeQuery($sql);

        self::assertSame(
            [
                ['array' => '{{ROLE_USER,ROLE_ADMIN},{ROLE_AUTOMATIONS,ROLE_ADMIN}}'],
            ],
            $result->fetchAllAssociative(),
        );
    }

    #[Test]
    public function it_can_create_an_empty_array_constructor(): void
    {
        $array = new Vector();
        $sql = <<<EOSQL
            SELECT {$array->toSQL()}::integer[]
        EOSQL;

        $result = self::connection()->executeQuery($sql);

        self::assertSame(
            [
                ['array' => '{}'],
            ],
            $result->fetchAllAssociative(),
        );
    }
}
