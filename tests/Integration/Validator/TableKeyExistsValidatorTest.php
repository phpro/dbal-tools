<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Integration\Validator;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;
use Phpro\DbalTools\Schema\Table;
use Phpro\DbalTools\Test\Validator\DoctrineValidatorTestCase;
use Phpro\DbalTools\Validator\TableKeyExistsConstraint;
use Phpro\DbalTools\Validator\TableKeyExistsValidator;
use PhproTest\DbalTools\Fixtures\Type\Uuid;
use PHPUnit\Framework\Attributes\Test;
use Psl\Type\Exception\AssertException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;

final class TableKeyExistsValidatorTest extends DoctrineValidatorTestCase
{
    protected function createFixtures(): void
    {
        $ids = ['2c8c206c-0031-4c85-b36d-a7d5be16c138', '0c667837-9edb-474b-beba-a6e0c608d6d2'];

        foreach ($ids as $id) {
            self::connection()->insert(
                TableKeyExistsTable::name(),
                [
                    TableKeyExistsTableColumns::Id->value => $id,
                ],
                TableKeyExistsTable::columnTypes(),
            );
        }
    }

    protected static function schemaTables(): array
    {
        return [TableKeyExistsTable::class];
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        $this->validator = new TableKeyExistsValidator(
            self::connection()
        );

        return $this->validator;
    }

    #[Test]
    public function it_adds_a_violation_when_table_key_does_not_exist(): void
    {
        $table = TableKeyExistsTable::class;
        $column = TableKeyExistsTableColumns::Id;
        $value = '9e128344-6e0a-4913-ba2d-3a7d5f5cb09b';

        $constraint = new TableKeyExistsConstraint(
            table: $table,
            column: $column,
        );

        $this->validator->validate($value, $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(1, $violationList->count());
        self::assertSame('property.path', $violationList->get(0)->getPropertyPath());
    }

    #[Test]
    public function it_adds_no_violations_when_table_key_exists(): void
    {
        $table = TableKeyExistsTable::class;
        $column = TableKeyExistsTableColumns::Id;
        $value = '2c8c206c-0031-4c85-b36d-a7d5be16c138';

        $constraint = new TableKeyExistsConstraint(
            table: $table,
            column: $column,
        );

        $this->validator->validate($value, $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_violations_when_multiple_table_keys_do_not_exist(): void
    {
        $table = TableKeyExistsTable::class;
        $column = TableKeyExistsTableColumns::Id;

        $constraint = new TableKeyExistsConstraint(
            table: $table,
            column: $column,
        );

        $this->validator->validate([
            '3e5ae04e-64b7-4710-946e-05e48696c949',
            '0c667837-9edb-474b-beba-a6e0c608d6d2',
            Uuid::fromString('0c667837-9edb-474b-beba-a6e0c608d6d2'),
            Uuid::fromString('7b7d3cbd-d160-4f83-b06a-e7d5c9a6569e'),
            '0c667837-9edb-474b-beba-a6e0c608d6d2',
        ], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(2, $violationList->count());
        self::assertSame('property.path[0]', $violationList->get(0)->getPropertyPath());
        self::assertSame('property.path[3]', $violationList->get(1)->getPropertyPath());
    }

    #[Test]
    public function it_adds_no_violations_when_no_values_are_provided(): void
    {
        $table = TableKeyExistsTable::class;
        $column = TableKeyExistsTableColumns::Id;

        $constraint = new TableKeyExistsConstraint(
            table: $table,
            column: $column,
        );

        $this->validator->validate([], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_no_violations_when_null_value_id_provided(): void
    {
        $table = TableKeyExistsTable::class;
        $column = TableKeyExistsTableColumns::Id;

        $constraint = new TableKeyExistsConstraint(
            table: $table,
            column: $column,
        );

        $this->validator->validate(null, $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_no_violations_when_null_values_are_provided(): void
    {
        $table = TableKeyExistsTable::class;
        $column = TableKeyExistsTableColumns::Id;

        $constraint = new TableKeyExistsConstraint(
            table: $table,
            column: $column,
        );

        $this->validator->validate([null, null], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_no_violations_when_table_keys_exists(): void
    {
        $table = TableKeyExistsTable::class;
        $column = TableKeyExistsTableColumns::Id;

        $constraint = new TableKeyExistsConstraint(
            table: $table,
            column: $column,
        );

        $this->validator->validate([
            '0c667837-9edb-474b-beba-a6e0c608d6d2',
            '0c667837-9edb-474b-beba-a6e0c608d6d2',
            Uuid::fromString('0c667837-9edb-474b-beba-a6e0c608d6d2'),
            '0c667837-9edb-474b-beba-a6e0c608d6d2',
        ], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_can_throwable_invalid_argument_exception_with_invalid_table_class(): void
    {
        $table = 'invalid class';
        $column = TableKeyExistsTableColumns::Id;
        $value = '0c667837-9edb-474b-beba-a6e0c608d6d2';

        $constraint = new TableKeyExistsConstraint(
            table: $table,
            column: $column,
        );

        self::expectException(AssertException::class);
        $this->validator->validate($value, $constraint);
    }

    #[Test]
    public function it_has_constraint_settings(): void
    {
        $constraint = new TableKeyExistsConstraint(
            table: TableKeyExistsTable::class,
            column: TableKeyExistsTableColumns::Id,
        );

        self::assertSame(TableKeyExistsTable::class, $constraint->table);
        self::assertSame(TableKeyExistsTableColumns::Id, $constraint->column);
        self::assertSame(TableKeyExistsValidator::class, $constraint->validatedBy());
        self::assertSame([Constraint::PROPERTY_CONSTRAINT], $constraint->getTargets());
    }
}

class TableKeyExistsTable extends Table
{
    public static function name(): string
    {
        return 'table_key_exists_table';
    }

    public static function columns(): Columns
    {
        return Columns::for(TableKeyExistsTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        return new DoctrineTable(self::name(), [
            new DoctrineColumn(TableKeyExistsTableColumns::Id->value, Type::getType(Types::GUID)),
        ]);
    }
}

enum TableKeyExistsTableColumns: string implements TableColumnsInterface
{
    use TableColumnsTrait;

    case Id = 'id';

    public function linkedTableClass(): string
    {
        return TableKeyExistsTable::class;
    }
}
