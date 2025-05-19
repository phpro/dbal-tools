<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Validator;

use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Schema\Table;
use Symfony\Component\Validator\Constraint;

/**
 * @psalm-suppress MissingConstructor
 */
final class UniqueConstraint extends Constraint
{
    /**
     * @var class-string<Table>
     */
    public string $table;

    /**
     * @var array<string, TableColumnsInterface> - A mapping between property names and their respective columns
     */
    public array $columns;

    public string $message = 'The provided payload at properties "{{ properties }}" does not result in a unique record.';

    /**
     * When identifiers are set, they will be used in order to figure out if you are updating an existing record or creating a new one.
     *
     * @var array<string, TableColumnsInterface> - A mapping between property names and their respective columns
     */
    public array $identifiers = [];

    /**
     * Can be used to specify a path on which you want to display the error message.
     * If not set, it will be displayed at root object level.
     */
    public ?string $path = null;

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
