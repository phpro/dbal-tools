<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Schema\Sequence;
use Phpro\DbalTools\Schema\Table;
use Phpro\DbalTools\Test\Assert\DbalAssert;
use Phpro\DbalTools\Test\Manager\ConnectionManager;
use Phpro\DbalTools\Test\Manager\SchemaManager;
use Phpro\DbalTools\Test\Manager\TransactionManager;
use PHPUnit\Framework\TestCase;

abstract class DbalTestCase extends TestCase
{
    use DbalAssert;

    protected array $fixtures = [];

    /**
     * Create database fixtures for your tests.
     * You can use the private fixtures array to keep track of the persisted fixtures.
     *
     * @return void
     */
    protected function createFixtures(): void
    {
    }

    /**
     * List the table class-names that should be created for the test.
     *
     * @return list<class-string<Table>>
     */
    protected static function schemaTables(): array
    {
        return [];
    }

    /**
     * List the sequence class-names that should be created for the test.
     *
     * @return list<class-string<Sequence>>
     */
    protected static function schemaSequences(): array
    {
        return [];
    }

    /**
     * In this method, you can override or add dbal types.
     */
    protected static function overrideDbalTypes(): void
    {
    }

    public static function setUpBeforeClass(): void
    {
        static::overrideDbalTypes();
        $schemaManager = SchemaManager::instance();
        $schemaManager->createTables(static::schemaTables());
        $schemaManager->createSequences(static::schemaSequences());
    }

    protected function setUp(): void
    {
        TransactionManager::instance()->createSavepoint('blank_fixtures');
        $this->createFixtures();
    }

    protected function tearDown(): void
    {
        TransactionManager::instance()->rollbackSavepoint('blank_fixtures');
    }

    protected static function connection(): Connection
    {
        return ConnectionManager::getConnection();
    }

    protected static function getDatabasePlatform(): AbstractPlatform
    {
        return static::connection()->getDatabasePlatform();
    }

    /**
     * @no-named-arguments
     *
     * @param list<array<string, mixed>>                                                            $records
     * @param array<int<0,max>, string|ParameterType|Type>|array<string, string|ParameterType|Type> $types
     */
    protected static function insert(string $tableName, array $types, array ...$records): void
    {
        foreach ($records as $record) {
            static::connection()->insert($tableName, $record, $types);
        }
    }

    protected static function getRecord(string $table, Expression $where): array
    {
        $record = static::connection()->fetchAssociative("SELECT * FROM {$table} WHERE {$where->toSql()};");
        if (!is_array($record)) {
            throw new \RuntimeException('Record not found');
        }

        return $record;
    }
}
