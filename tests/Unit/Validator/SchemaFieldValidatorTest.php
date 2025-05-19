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
use PhproTest\DbalTools\Fixtures\Schema\UsersTable;
use PhproTest\DbalTools\Fixtures\Schema\UsersTableColumns;
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
        $constraint = new SchemaFieldConstraint([
            'table' => UsersTable::class,
            'column' => UsersTableColumns::FirstName,
        ]);

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
        $constraint = new SchemaFieldConstraint([
            'table' => UsersTable::class,
            'column' => UsersTableColumns::FirstName,
        ]);

        $this->validator->validate(
            '123456789',
            $constraint
        );
        $violationList = $this->context->getViolations();
        $this->assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_adds_a_violation_when_table_is_not_found(): void
    {
        $constraint = new SchemaFieldConstraint([
            'table' => 'table not found',
            'column' => UsersTableColumns::FirstName,
        ]);

        self::expectException(AssertException::class);
        $this->validator->validate(
            'Lorem Ipsum is slechts een proeftekst.',
            $constraint
        );
    }

    #[Test]
    public function it_adds_a_violation_when_column_is_not_found(): void
    {
        $constraint = new SchemaFieldConstraint([
            'table' => UsersTable::class,
            'column' => NonExistingTableColumns::NonExisting,
        ]);

        self::expectException(ColumnDoesNotExist::class);
        $this->validator->validate(
            'Lorem Ipsum is slechts een proeftekst.',
            $constraint
        );
    }

    #[Test]
    public function it_is_valid_when_column_length_is_null(): void
    {
        $constraint = new SchemaFieldConstraint([
            'table' => UsersTable::class,
            'column' => UsersTableColumns::LastName,
        ]);

        $this->validator->validate(
            'Lorem Ipsum is slechts een proeftekst uit het drukkerij- en zetterijwezen. Lorem Ipsum is de standaard proeftekst in deze bedrijfstak sinds de 16e eeuw, toen een onbekende drukker een zethaak met letters nam en ze door elkaar husselde om een font-catalogus te maken. Het heeft niet alleen vijf eeuwen overleefd maar is ook, vrijwel onveranderd, overgenomen in elektronische letterzetting.',
            $constraint
        );

        $violationList = $this->context->getViolations();
        $this->assertSame(0, $violationList->count());
    }

    #[Test]
    public function it_has_constraint_settings(): void
    {
        $constraint = new SchemaFieldConstraint([
            'table' => UsersTable::class,
            'column' => UsersTableColumns::FirstName,
        ]);

        $this->assertSame(UsersTable::class, $constraint->table);
        $this->assertSame(UsersTableColumns::FirstName, $constraint->column);
        $this->assertSame(SchemaFieldValidator::class, $constraint->validatedBy());
        $this->assertSame([Constraint::PROPERTY_CONSTRAINT], $constraint->getTargets());
    }
}
