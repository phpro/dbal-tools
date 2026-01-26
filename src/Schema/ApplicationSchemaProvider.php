<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence as DoctrineSequence;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\Migrations\Provider\SchemaProvider;

use function Psl\Vec\map;

final readonly class ApplicationSchemaProvider implements SchemaProvider
{
    /**
     * @param iterable<int, Table>    $tables
     * @param iterable<int, Sequence> $sequences
     */
    public function __construct(
        private iterable $tables,
        private iterable $sequences,
    ) {
    }

    public function createSchema(): Schema
    {
        $schemaConfig = new SchemaConfig();
        $schemaConfig->setName('public');

        return new Schema(
            map(
                $this->tables,
                static fn (Table $table): DoctrineTable => $table->createTable()
            ),
            map(
                $this->sequences,
                static fn (Sequence $sequence): DoctrineSequence => $sequence->createSequence()
            ),
            $schemaConfig
        );
    }
}
