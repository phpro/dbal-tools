<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Expression;

use Doctrine\DBAL\Schema\Sequence as DoctrineSequence;
use Phpro\DbalTools\Expression\NextVal;
use Phpro\DbalTools\Schema\Sequence;
use Phpro\DbalTools\Test\DbalReaderTestCase;
use PHPUnit\Framework\Attributes\Test;

final class NextValTest extends DbalReaderTestCase
{
    protected function createFixtures(): void
    {
    }

    protected static function schemaTables(): array
    {
        return [];
    }

    protected static function schemaSequences(): array
    {
        return [
            SchemaSequence::class,
        ];
    }

    #[Test]
    public function it_can_use_next_val(): void
    {
        $qb = $this->connection()->createQueryBuilder();
        $qb->select(
            (new NextVal(SchemaSequence::name()))->toSQL()
        );

        $actualResults = $qb->fetchNumeric();
        if (!$actualResults) {
            $this->fail('No results found');
        }

        self::assertSame($actualResults[0], 1);
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
