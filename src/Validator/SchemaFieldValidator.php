<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Validator;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
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

        $range = self::integerRange(self::typeNameOf($column));
        if (null !== $range && is_int($value) && ($value < $range[0] || $value > $range[1])) {
            $this->context->buildViolation('This value should be between {{ min }} and {{ max }}.')
                ->setParameter('{{ min }}', (string) $range[0])
                ->setParameter('{{ max }}', (string) $range[1])
                ->setTranslationDomain('validators')
                ->addViolation();
        }
    }

    /**
     * DBAL 4.5 deprecates Column::getType() in favour of getTypeName(), which does not exist before 4.5.
     *
     * Each DBAL version only runs one branch, so this stays on one line to keep line coverage at 100%.
     *
     * @psalm-suppress DeprecatedMethod - Only deprecated from DBAL 4.5, where the getTypeName() branch is taken.
     * @psalm-suppress MixedReturnStatement - Before DBAL 4.5 getTypeName() is unknown, so its result is mixed.
     * @psalm-suppress UnusedPsalmSuppress - Each suppression only applies to one DBAL version.
     */
    private static function typeNameOf(Column $column): string
    {
        return method_exists($column, 'getTypeName') ? $column->getTypeName() : Type::lookupName($column->getType());
    }

    /**
     * A BIGINT column needs no range: its bounds are those of a PHP int.
     *
     * @return array{int, int}|null
     */
    private static function integerRange(string $type): ?array
    {
        return match ($type) {
            Types::SMALLINT => [-32_768, 32_767],
            Types::INTEGER => [-2_147_483_648, 2_147_483_647],
            default => null,
        };
    }
}
