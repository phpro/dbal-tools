<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Validator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Expression\Cast;
use Phpro\DbalTools\Expression\Comparison;
use Phpro\DbalTools\Expression\Composite;
use Phpro\DbalTools\Expression\Expression;
use Phpro\DbalTools\Expression\Factory\NamedParameter;
use Phpro\DbalTools\Expression\Lower;
use Phpro\DbalTools\Expression\SqlExpression;
use Phpro\DbalTools\Schema\Table;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function Psl\Iter\reduce_with_keys;
use function Psl\Type\class_string;
use function Psl\Type\dict;
use function Psl\Type\instance_of;
use function Psl\Type\string;
use function Psl\Vec\map;
use function Psl\Vec\map_with_key;

final class UniqueValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Connection $connection,
        private readonly PropertyAccessor $accessor,
    ) {
    }

    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param object           $value
     * @param UniqueConstraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $table = class_string(Table::class)->assert($constraint->table);
        $tableName = $table::name();
        $columns = dict(string(), instance_of(TableColumnsInterface::class))->assert($constraint->columns);
        $identifiers = dict(string(), instance_of(TableColumnsInterface::class))->assert($constraint->identifiers);

        if (!count($columns)) {
            $this->context
                ->buildViolation(
                    new TranslatableMessage('At least one column must be specified for detecting uniqueness.', [], 'validators')
                )
                ->addViolation();

            return;
        }

        $comparable = static fn (Expression $expression): Expression => match (true) {
            $constraint->caseInsensitive => new Lower(Cast::varchar($expression)),
            default => $expression,
        };

        $qb = $this->connection->createQueryBuilder();
        $qb
            ->select(
                SqlExpression::true()->toSQL(),
                ...map(
                    $identifiers,
                    fn (TableColumnsInterface $column): string => $column->onTable($tableName)->toSQL()
                )
            )
            ->from($tableName)
            ->where(
                Composite::and(
                    ...map_with_key(
                        $columns,
                        fn (string $property, TableColumnsInterface $column): Comparison => Comparison::equal(
                            $comparable($column->onTable($tableName)),
                            $comparable(NamedParameter::createForTableColumn($qb, $column, $this->accessor->getValue($value, $property)))
                        )
                    )
                )->toSQL()
            );

        $res = $qb->fetchAssociative();
        if (false === $res) {
            return;
        }

        $isCurrent = count($identifiers) && reduce_with_keys(
            $identifiers,
            fn (bool $isCurrent, string $property, TableColumnsInterface $column): bool => $isCurrent
                && array_key_exists((string) $column->value, $res)
                && $res[(string) $column->value] === $this->connection->convertToDatabaseValue(
                    $this->accessor->getValue($value, $property),
                    Type::lookupName($column->columnType())
                ),
            true
        );

        if (!$isCurrent) {
            $violationBuilder = $this->context->buildViolation($constraint->message, [
                '{{ properties }}' => implode(', ', array_keys($columns)),
            ]);

            if (null !== $constraint->path) {
                $violationBuilder->atPath($constraint->path);
            }

            $violationBuilder->addViolation();
        }
    }
}
