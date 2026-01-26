<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Validator;

use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Schema\Table;
use Symfony\Component\Validator\Constraint;

final class SchemaFieldConstraint extends Constraint
{
    /**
     * @param class-string<Table> $table
     * @param list<string>|null   $groups
     */
    public function __construct(
        public string $table,
        public TableColumnsInterface $column,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

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
