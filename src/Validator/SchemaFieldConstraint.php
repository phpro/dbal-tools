<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Validator;

use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Schema\Table;
use Symfony\Component\Validator\Constraint;

/**
 * @psalm-suppress MissingConstructor
 */
final class SchemaFieldConstraint extends Constraint
{
    /** @var class-string<Table> */
    public string $table;
    public TableColumnsInterface $column;

    public function validatedBy(): string
    {
        return SchemaFieldValidator::class;
    }

    public function getTargets(): array
    {
        return [
            self::PROPERTY_CONSTRAINT,
        ];
    }
}
