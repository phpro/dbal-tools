<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Unit\Validator;

use Doctrine\DBAL\Schema\Exception\ColumnDoesNotExist;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Phpro\DbalTools\Validator\SchemaFieldConstraint;
use Phpro\DbalTools\Validator\SchemaFieldValidator;
use PhproTest\DbalTools\Fixtures\Schema\NonExistingTableColumns;
use PhproTest\DbalTools\Fixtures\Schema\NumericTableColumns;
use PhproTest\DbalTools\Fixtures\Schema\UsersTable;
use PhproTest\DbalTools\Fixtures\Schema\UsersTableColumns;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psl\Type\Exception\AssertException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class SchemaFieldValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        $usersTable = new Table('users');
        $usersTable->addColumn('first_name', Types::STRING, ['length' => 10]);
        $usersTable->addColumn('last_name', Types::STRING, []);
        $usersTable->addColumn('small_int', Types::SMALLINT, []);
        $usersTable->addColumn('integer', Types::INTEGER, []);
        $usersTable->addColumn('big_int', Types::BIGINT, []);

        $this->validator = new SchemaFieldValidator(
            new Schema([
                $usersTable,
            ])
        );

        return $this->validator;
    }

    #[Test]
    public function it_adds_a_violation_when_length_of_column_is_too_long(): void
    {
        $constraint = new SchemaFieldConstraint(
            table: UsersTable::class,
            column: UsersTableColumns::FirstName,
        );

        $this->validator->validate(
            '123456789FOUT',
            $constraint
        );
        $violationList = $this->context->getViolations();

        $this->assertSame(1, $violationList->count());
    }

    #[Test]
    public function it_adds_no_violations_when_length_of_column_is_correct(): void
    {
        $constraint = new SchemaFieldConstraint(
            table: UsersTable::class,
            column: UsersTableColumns::FirstName,
        );

        $this->validator->validate(
            '123456789',
            $constraint
        );
        $violationList = $this->context->getViolations();
        $this->assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_no_violations_when_value_is_null(): void
    {
        $constraint = new SchemaFieldConstraint(
            table: UsersTable::class,
            column: UsersTableColumns::FirstName,
        );

        $this->validator->validate(
            null,
            $constraint
        );
        $violationList = $this->context->getViolations();
        $this->assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_a_violation_when_table_is_not_found(): void
    {
        $constraint = new SchemaFieldConstraint(
            table: 'table not found',
            column: UsersTableColumns::FirstName,
        );

        self::expectException(AssertException::class);
        $this->validator->validate(
            'Lorem Ipsum is slechts een proeftekst.',
            $constraint
        );
    }

    #[Test]
    public function it_adds_a_violation_when_column_is_not_found(): void
    {
        $constraint = new SchemaFieldConstraint(
            table: UsersTable::class,
            column: NonExistingTableColumns::NonExisting,
        );

        self::expectException(ColumnDoesNotExist::class);
        $this->validator->validate(
            'Lorem Ipsum is slechts een proeftekst.',
            $constraint
        );
    }

    #[Test]
    public function it_is_valid_when_column_length_is_null(): void
    {
        $constraint = new SchemaFieldConstraint(
            table: UsersTable::class,
            column: UsersTableColumns::LastName,
        );

        $this->validator->validate(
            'Lorem Ipsum is slechts een proeftekst uit het drukkerij- en zetterijwezen. Lorem Ipsum is de standaard proeftekst in deze bedrijfstak sinds de 16e eeuw, toen een onbekende drukker een zethaak met letters nam en ze door elkaar husselde om een font-catalogus te maken. Het heeft niet alleen vijf eeuwen overleefd maar is ook, vrijwel onveranderd, overgenomen in elektronische letterzetting.',
            $constraint
        );

        $violationList = $this->context->getViolations();
        $this->assertSame(0, $violationList->count());
    }

    #[Test]
    #[DataProvider('provideOutOfRangeIntegers')]
    public function it_adds_a_violation_when_an_integer_is_outside_the_column_range(
        NumericTableColumns $column,
        int $value,
        string $min,
        string $max,
    ): void {
        $constraint = new SchemaFieldConstraint(
            table: UsersTable::class,
            column: $column,
        );

        $this->validator->validate($value, $constraint);

        $this->buildViolation('This value should be between {{ min }} and {{ max }}.')
            ->setParameter('{{ min }}', $min)
            ->setParameter('{{ max }}', $max)
            ->setTranslationDomain('validators')
            ->assertRaised();
    }

    public static function provideOutOfRangeIntegers(): iterable
    {
        yield 'smallint below' => [NumericTableColumns::SmallInt, -32_769, '-32768', '32767'];
        yield 'smallint above' => [NumericTableColumns::SmallInt, 32_768, '-32768', '32767'];
        yield 'integer below' => [NumericTableColumns::Integer, -2_147_483_649, '-2147483648', '2147483647'];
        yield 'integer above' => [NumericTableColumns::Integer, 2_147_483_648, '-2147483648', '2147483647'];
    }

    #[Test]
    #[DataProvider('provideInRangeIntegers')]
    public function it_adds_no_violations_when_an_integer_fits_the_column(NumericTableColumns $column, int $value): void
    {
        $constraint = new SchemaFieldConstraint(
            table: UsersTable::class,
            column: $column,
        );

        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public static function provideInRangeIntegers(): iterable
    {
        yield 'smallint min' => [NumericTableColumns::SmallInt, -32_768];
        yield 'smallint max' => [NumericTableColumns::SmallInt, 32_767];
        yield 'integer min' => [NumericTableColumns::Integer, -2_147_483_648];
        yield 'integer max' => [NumericTableColumns::Integer, 2_147_483_647];
        yield 'bigint min' => [NumericTableColumns::BigInt, PHP_INT_MIN];
        yield 'bigint max' => [NumericTableColumns::BigInt, PHP_INT_MAX];
    }

    #[Test]
    public function it_adds_no_violations_when_an_integer_column_receives_null(): void
    {
        $constraint = new SchemaFieldConstraint(
            table: UsersTable::class,
            column: NumericTableColumns::Integer,
        );

        $this->validator->validate(null, $constraint);

        $this->assertNoViolation();
    }

    #[Test]
    public function it_has_constraint_settings(): void
    {
        $constraint = new SchemaFieldConstraint(
            table: UsersTable::class,
            column: UsersTableColumns::FirstName,
        );

        $this->assertSame(UsersTable::class, $constraint->table);
        $this->assertSame(UsersTableColumns::FirstName, $constraint->column);
        $this->assertSame(SchemaFieldValidator::class, $constraint->validatedBy());
        $this->assertSame([Constraint::PROPERTY_CONSTRAINT], $constraint->getTargets());
    }
}
