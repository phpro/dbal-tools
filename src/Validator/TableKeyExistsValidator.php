<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Validator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Phpro\DbalTools\Expression\Distinct;
use Phpro\DbalTools\Expression\Factory\NamedParameter;
use Phpro\DbalTools\Expression\In;
use Phpro\DbalTools\Expression\JsonbAggStrict;
use Phpro\DbalTools\Schema\Table;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function Psl\Json\typed;
use function Psl\Type\class_string;
use function Psl\Type\mixed_vec;
use function Psl\Type\string;
use function Psl\Vec\filter_nulls;
use function Psl\Vec\map;
use function Psl\Vec\values;

final class TableKeyExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param TableKeyExistsConstraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $table = class_string(Table::class)->assert($constraint->table);
        $tableName = $table::name();
        $column = $constraint->column;
        $columnOnTable = $column->onTable($tableName);

        /** @var list<mixed> $in */
        $in = filter_nulls(is_iterable($value) ? values($value) : [$value]);
        if (!$in) {
            return;
        }

        $qb = $this->connection->createQueryBuilder();
        $qb->select(
            (new JsonbAggStrict(new Distinct($columnOnTable)))->toSQL()
        )
            ->from($tableName)
            ->where(
                In::fromIterable(
                    $columnOnTable,
                    $in,
                    static fn (mixed $current) => NamedParameter::createForTableColumn($qb, $column, $current),
                )->toSQL()
            );

        $json = $qb->fetchNumeric();
        $json = (false === $json) ? '[]' : string()->assert($json[0] ?? '[]');

        $results = typed($json, mixed_vec());
        if (count($results) === count($in)) {
            return;
        }

        if (!is_iterable($value)) {
            $this->context->buildViolation('The provided value could not be found.')
                ->setTranslationDomain('validators')
                ->addViolation();

            return;
        }

        $columnType = Type::lookupName($column->columnType());
        $missing = array_diff(
            map($in, fn ($current): mixed => $this->connection->convertToDatabaseValue($current, $columnType)),
            $results
        );
        foreach ($missing as $path => $_) {
            $this->context->buildViolation('The provided value could not be found.')
                ->setTranslationDomain('validators')
                ->atPath('['.(string) $path.']')
                ->addViolation();
        }
    }
}
