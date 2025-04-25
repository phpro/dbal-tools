<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test\DBAL\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;
use function Psl\Dict\pull_with_key;
use function Psl\Iter\contains;
use function Psl\Vec\map;

abstract class DbalTypeTestCase extends DbalReaderTestCase
{
    abstract protected function getType(): Type;

    abstract protected function expectSqlDeclaration(AbstractPlatform $platform, array $columns): string;

    #[Test]
    public function it_expects_a_sql_declaration(): void
    {
        $platform = self::getDatabasePlatform();
        $actual = $this->getType()->getSQLDeclaration($columns = $this->provideDeclarationColumnOptions(), $platform);

        self::assertSame($this->expectSqlDeclaration($platform, $columns), $actual);
    }

    protected function provideDeclarationColumnOptions(): array
    {
        return [];
    }

    protected function convertSqlResult(Result $result, array $columns): array
    {
        $type = $this->getType();
        $platform = $this->getDatabasePlatform();

        return map(
            $result->fetchAllAssociative(),
            static fn (array $row): array => pull_with_key(
                $row,
                static fn (string $key, mixed $value) => contains($columns, $key) ? $type->convertToPHPValue($value, $platform) : $value,
                static fn (string $key, mixed $value) => $key,
            )
        );
    }
}
