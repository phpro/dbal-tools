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
use Phpro\DbalTools\Validator\UniqueConstraint;
use Phpro\DbalTools\Validator\UniqueValidator;
use PHPUnit\Framework\Attributes\Test;
use Psl\Type\Exception\AssertException;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;

final class UniqueValidatorTest extends DoctrineValidatorTestCase
{
    protected static function schemaTables(): array
    {
        return [UniqueValidatorTable::class];
    }

    protected function createFixtures(): void
    {
        self::connection()->insert(
            UniqueValidatorTable::name(),
            [
                UniqueValidatorTableColumns::Id->value => 'f7e2c2df-1786-497b-b71e-43b66073ecc6',
                UniqueValidatorTableColumns::FirstName->value => 'Jos',
                UniqueValidatorTableColumns::LastName->value => 'Bos',
                UniqueValidatorTableColumns::Email->value => 'josbos@dispostable.com',
            ],
            UniqueValidatorTable::columnTypes(),
        );
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new UniqueValidator(
            self::connection(),
            (new PropertyAccessorBuilder())->getPropertyAccessor(),
        );
    }

    #[Test]
    public function it_adds_a_violation_when_no_column_was_specified(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [],
        ]);

        $this->validator->validate((object) [], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(1, $violationList->count());
        self::assertSame(
            'At least one column must be specified for detecting uniqueness.',
            $violationList->get(0)->getMessage()
        );
    }

    #[Test]
    public function it_adds_a_violation_when_single_column_is_not_unique(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'id' => UniqueValidatorTableColumns::Id,
            ],
        ]);

        $this->validator->validate((object) ['id' => 'f7e2c2df-1786-497b-b71e-43b66073ecc6'], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(1, $violationList->count());
        self::assertSame(
            'The provided payload at properties "{{ properties }}" does not result in a unique record.',
            $violationList->get(0)->getMessage()
        );
    }

    #[Test]
    public function it_is_possible_to_configure_error_messages_and_path(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'id' => UniqueValidatorTableColumns::Id,
            ],
            'path' => 'myproperty',
            'message' => 'custom error',
        ]);

        $this->validator->validate((object) ['id' => 'f7e2c2df-1786-497b-b71e-43b66073ecc6'], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(1, $violationList->count());
        self::assertSame(
            'custom error',
            $violationList->get(0)->getMessage()
        );
        self::assertSame('property.path.myproperty', $violationList->get(0)->getPropertyPath());
    }

    #[Test]
    public function it_adds_no_violation_when_single_column_is_unique(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'id' => UniqueValidatorTableColumns::Id,
            ],
        ]);

        $this->validator->validate((object) ['id' => 'f082b7a5-f556-4061-8158-57f9d18606b3'], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_a_violation_when_multi_column_is_not_unique(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'firstName' => UniqueValidatorTableColumns::FirstName,
                'lastName' => UniqueValidatorTableColumns::LastName,
            ],
        ]);

        $this->validator->validate((object) ['firstName' => 'Jos', 'lastName' => 'Bos'], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(1, $violationList->count());
        self::assertSame(
            'The provided payload at properties "{{ properties }}" does not result in a unique record.',
            $violationList->get(0)->getMessage()
        );
    }

    #[Test]
    public function it_adds_no_violation_when_multi_column_is_unique(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'firstName' => UniqueValidatorTableColumns::FirstName,
                'lastName' => UniqueValidatorTableColumns::LastName,
            ],
        ]);

        $this->validator->validate((object) ['firstName' => 'Jos', 'lastName' => 'Mos'], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_no_violation_when_multi_column_is_not_unique_but_the_identifiers_are_matching(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'firstName' => UniqueValidatorTableColumns::FirstName,
                'lastName' => UniqueValidatorTableColumns::LastName,
            ],
            'identifiers' => [
                'id' => UniqueValidatorTableColumns::Id,
                'email' => UniqueValidatorTableColumns::Email,
            ],
        ]);

        $this->validator->validate((object) [
            'id' => 'f7e2c2df-1786-497b-b71e-43b66073ecc6',
            'email' => 'josbos@dispostable.com',
            'firstName' => 'Jos',
            'lastName' => 'Bos',
        ], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_violation_when_multi_column_is_not_unique_and_not_all_identifiers_are_matching(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'firstName' => UniqueValidatorTableColumns::FirstName,
                'lastName' => UniqueValidatorTableColumns::LastName,
            ],
            'identifiers' => [
                'id' => UniqueValidatorTableColumns::Id,
                'email' => UniqueValidatorTableColumns::Email,
            ],
        ]);

        $this->validator->validate((object) [
            'id' => 'f7e2c2df-1786-497b-b71e-43b66073ecc6',
            'email' => 'notmatching@dispostable.com',
            'firstName' => 'Jos',
            'lastName' => 'Bos',
        ], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(1, $violationList->count());
        self::assertSame(
            'The provided payload at properties "{{ properties }}" does not result in a unique record.',
            $violationList->get(0)->getMessage()
        );
    }

    #[Test]
    public function it_can_throwable_invalid_argument_exception_with_invalid_table_class(): void
    {
        $table = 'invalid class';
        $column = UniqueValidatorTableColumns::Id;
        $value = '0c667837-9edb-474b-beba-a6e0c608d6d2';

        $constraint = new UniqueConstraint([
            'table' => $table,
            'columns' => ['id' => $column],
        ]);

        self::expectException(AssertException::class);
        $this->validator->validate($value, $constraint);
    }

    #[Test]
    public function it_has_constraint_settings(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => ['email' => UniqueValidatorTableColumns::Email],
            'identifiers' => ['id' => UniqueValidatorTableColumns::Id],
            'path' => 'property',
            'message' => 'custom error',
            'caseInsensitive' => true,
        ]);

        self::assertSame(UniqueValidatorTable::class, $constraint->table);
        self::assertSame(['email' => UniqueValidatorTableColumns::Email], $constraint->columns);
        self::assertSame(['id' => UniqueValidatorTableColumns::Id], $constraint->identifiers);
        self::assertSame('property', $constraint->path);
        self::assertSame('custom error', $constraint->message);
        self::assertTrue($constraint->caseInsensitive);
        self::assertSame(UniqueValidator::class, $constraint->validatedBy());
        self::assertSame([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    #[Test]
    public function it_adds_a_violation_when_single_column_is_not_unique_case_insensitive(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'email' => UniqueValidatorTableColumns::Email,
            ],
            'caseInsensitive' => true,
        ]);

        $this->validator->validate((object) ['email' => 'JOSBOS@DISPOSTABLE.COM'], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(1, $violationList->count());
        self::assertSame(
            'The provided payload at properties "{{ properties }}" does not result in a unique record.',
            $violationList->get(0)->getMessage()
        );
    }

    #[Test]
    public function it_adds_no_violation_when_single_column_is_unique_case_insensitive(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'email' => UniqueValidatorTableColumns::Email,
            ],
            'caseInsensitive' => true,
        ]);

        $this->validator->validate((object) ['email' => 'UNIQUE@EXAMPLE.COM'], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_a_violation_when_multi_column_is_not_unique_case_insensitive(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'firstName' => UniqueValidatorTableColumns::FirstName,
                'lastName' => UniqueValidatorTableColumns::LastName,
            ],
            'caseInsensitive' => true,
        ]);

        $this->validator->validate((object) ['firstName' => 'JOS', 'lastName' => 'BOS'], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(1, $violationList->count());
        self::assertSame(
            'The provided payload at properties "{{ properties }}" does not result in a unique record.',
            $violationList->get(0)->getMessage()
        );
    }

    #[Test]
    public function it_adds_no_violation_when_case_sensitive_and_different_case(): void
    {
        $constraint = new UniqueConstraint([
            'table' => UniqueValidatorTable::class,
            'columns' => [
                'email' => UniqueValidatorTableColumns::Email,
            ],
            'caseInsensitive' => false,
        ]);

        $this->validator->validate((object) ['email' => 'JOSBOS@DISPOSTABLE.COM'], $constraint);

        $violationList = $this->context->getViolations();
        self::assertSame(0, $violationList->count());
    }
}

class UniqueValidatorTable extends Table
{
    public static function name(): string
    {
        return 'unique_validator_table';
    }

    public static function columns(): Columns
    {
        return Columns::for(UniqueValidatorTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        return new DoctrineTable(self::name(), [
            new DoctrineColumn(UniqueValidatorTableColumns::Id->value, Type::getType(Types::GUID)),
            new DoctrineColumn(UniqueValidatorTableColumns::FirstName->value, Type::getType(Types::STRING)),
            new DoctrineColumn(UniqueValidatorTableColumns::LastName->value, Type::getType(Types::STRING)),
            new DoctrineColumn(UniqueValidatorTableColumns::Email->value, Type::getType(Types::STRING)),
        ]);
    }
}

enum UniqueValidatorTableColumns: string implements TableColumnsInterface
{
    use TableColumnsTrait;

    case Id = 'id';
    case FirstName = 'first_name';
    case LastName = 'last_name';
    case Email = 'email';

    public function linkedTableClass(): string
    {
        return UniqueValidatorTable::class;
    }
}
