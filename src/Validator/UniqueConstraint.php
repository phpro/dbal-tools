<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Validator;

use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Schema\Table;
use Symfony\Component\Validator\Constraint;

final class UniqueConstraint extends Constraint
{
    /**
     * @param class-string<Table>                  $table
     * @param array<string, TableColumnsInterface> $columns         A mapping between property names and their respective columns
     * @param array<string, TableColumnsInterface> $identifiers     When set, used to figure out if updating an existing record or creating a new one
     * @param bool                                 $caseInsensitive When true, the comparison will be case-insensitive using LOWER()
     * @param string|null                          $path            Path on which to display the error message. If not set, displayed at root object level.
     * @param list<string>|null                    $groups
     */
    public function __construct(
        public string $table,
        public array $columns,
        public string $message = 'The provided payload at properties "{{ properties }}" does not result in a unique record.',
        public array $identifiers = [],
        public bool $caseInsensitive = false,
        public ?string $path = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function validatedBy(): string
    {
        return UniqueValidator::class;
    }

    public function getTargets(): array
    {
        return [
            self::CLASS_CONSTRAINT,
        ];
    }
}
