<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Validator;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Schema\Table;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function Psl\Type\class_string;
use function Psl\Type\string;

final class SchemaFieldValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Schema $schema,
    ) {
    }

    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param mixed                 $value
     * @param SchemaFieldConstraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $table = $this->schema->getTable(
            class_string(Table::class)->assert($constraint->table)::name()
        );
        $column = $table->getColumn(string()->coerce($constraint->column->value));
        $length = $column->getLength();

        if (null !== $length && mb_strlen(string()->coerce($value ?? '')) > $length) {
            $this->context->buildViolation('The maximum number of allowed characters is {{ maxLength }}.')
                ->setParameter('{{ maxLength }}', (string) $length)
                ->setTranslationDomain('validators')
                ->addViolation();
        }

        $range = self::integerRange($constraint->column->columnType());
        if (null !== $range && is_int($value) && ($value < $range[0] || $value > $range[1])) {
            $this->context->buildViolation('This value should be between {{ min }} and {{ max }}.')
                ->setParameter('{{ min }}', (string) $range[0])
                ->setParameter('{{ max }}', (string) $range[1])
                ->setTranslationDomain('validators')
                ->addViolation();
        }
    }

    /**
     * Matched on the class rather than the type name, so a project type registered over or next to
     * `integer` still gets its range. A BIGINT column needs no range: its bounds are those of a PHP int.
     *
     * @return array{int, int}|null
     */
    private static function integerRange(Type $type): ?array
    {
        return match (true) {
            $type instanceof SmallIntType => [-32_768, 32_767],
            $type instanceof IntegerType => [-2_147_483_648, 2_147_483_647],
            default => null,
        };
    }
}
