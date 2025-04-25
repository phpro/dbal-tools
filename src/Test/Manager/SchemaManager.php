<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Sequence as DoctrineSequence;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Phpro\DbalTools\Schema\Sequence;
use Phpro\DbalTools\Schema\Table;
use function Psl\Vec\map;

final readonly class SchemaManager
{
    public static function instance(): self
    {
        static $connection = ConnectionManager::getConnection();
        static $schemaManager = $connection->createSchemaManager();

        return new self($connection, $schemaManager);
    }

    public function __construct(
        private Connection $connection,
        private AbstractSchemaManager $schemaManager,
    ) {
    }

    /** @psalm-param list<class-string<Table>> $schemaTables */
    public function createTables(array $schemaTables): void
    {
        if (!\count($schemaTables)) {
            return;
        }

        $schemaManager = $this->schemaManager;
        $tableNames = $schemaManager->listTableNames();
        /** @psalm-var class-string<Table> $schemaTable */
        foreach ($schemaTables as $schemaTable) {
            if (\in_array($schemaTable::name(), $tableNames, true)) {
                continue;
            }

            self::createDoctrineTable($schemaTable::createTable());
        }
    }

    public function createDoctrineTable(DoctrineTable $table): void
    {
        $schemaManager = $this->schemaManager;
        if (!$schemaManager->tableExists($table->getName())) {
            $schemaManager->createTable($table);
        }
    }

    /** @psalm-param list<class-string<Table>|string> $schemaTables */
    public function dropTables(array $schemaTables = []): void
    {
        if (!\count($schemaTables)) {
            return;
        }

        $this->connection->executeStatement(\array_reduce($schemaTables,
            /** @param class-string<Table> $schemaTable */
            static fn (string $sql, string $schemaTable): string => $sql
                .'DROP TABLE IF EXISTS '
                .(\str_contains($schemaTable, '\\') ? $schemaTable::name() : $schemaTable)
                .' CASCADE;',
            ''
        ));
    }

    /** @psalm-param list<class-string<Sequence>> $schemaTables */
    public function createSequences(array $schemaSequences = []): void
    {
        if (!\count($schemaSequences)) {
            return;
        }

        $schemaManager = $this->schemaManager;
        $sequenceNames = map(
            $schemaManager->listSequences(), fn (DoctrineSequence $sequence): string => $sequence->getName()
        );

        /** @psalm-var class-string<Sequence> $schemaSequence */
        foreach ($schemaSequences as $schemaSequence) {
            if (\in_array($schemaSequence::name(), $sequenceNames, true)) {
                continue;
            }

            $schemaManager->createSequence($schemaSequence::createSequence());
        }
    }

    public function dropSequences(): void
    {
        $sequences = $this->schemaManager->listSequences();
        if (!\count($sequences)) {
            return;
        }

        $this->connection->executeStatement(\array_reduce($sequences,
            static fn (string $sql, DoctrineSequence $sequence): string => $sql
                ."DROP SEQUENCE IF EXISTS {$sequence->getName()} CASCADE;",
            ''
        ));
    }
}
