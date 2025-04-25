<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Schema;

use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Schema\ApplicationSchemaProvider;
use Phpro\DbalTools\Schema\Sequence;
use Phpro\DbalTools\Schema\Table;
use Doctrine\DBAL\Schema\Sequence as DoctrineSequence;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ApplicationSchemaProviderTest extends TestCase
{
    #[Test]
    public function it_can_provide_application_schema(): void
    {
        $tables = [new SchemaTable()];
        $sequences = [new SchemaSequence()];
        $provider = new ApplicationSchemaProvider($tables, $sequences);

        $schema = $provider->createSchema();
        self::assertTrue($schema->hasTable(SchemaTable::name()));
        self::assertTrue($schema->hasSequence(SchemaSequence::name()));
    }
}

final class SchemaSequence extends Sequence
{
    public static function name(): string
    {
        return 'example_schema_sequence';
    }

    public static function createSequence(): DoctrineSequence
    {
        return new DoctrineSequence(self::name());
    }
}

final class SchemaTable extends Table
{
    public static function name(): string
    {
        return 'example_schema_table';
    }

    public static function createTable(): DoctrineTable
    {
        return new DoctrineTable(self::name());
    }

    public static function columns(): Columns
    {
        return Columns::for(__CLASS__);
    }
}
