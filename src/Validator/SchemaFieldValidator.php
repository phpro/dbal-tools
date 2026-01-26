<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Validator;

use Doctrine\DBAL\Schema\Schema;
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
    }
}
