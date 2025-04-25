<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test\Assert;

use Doctrine\DBAL\Connection;
use Phpro\DbalTools\Expression\Expression;
use PHPUnit\Framework\Assert;

trait DbalAssert
{
    abstract protected static function connection(): Connection;

    public static function assertRecordExists(string $table, Expression $expression): void
    {
        Assert::assertTrue((bool) static::connection()->fetchOne("SELECT TRUE FROM {$table} WHERE {$expression->toSql()};"));
    }

    public static function assertRecordDoesNotExist(string $table, Expression $expression): void
    {
        Assert::assertFalse((bool) static::connection()->fetchOne("SELECT TRUE FROM {$table} WHERE {$expression->toSql()};"));
    }

    public static function assertRecordsCount(int $expectedCount, string $table, Expression $expression): void
    {
        Assert::assertSame(
            $expectedCount,
            (int) static::connection()->fetchOne("SELECT COUNT({$expression->toSQL()}) FROM {$table};")
        );
    }
}
