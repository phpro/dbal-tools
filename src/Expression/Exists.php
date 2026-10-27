<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * The subquery's parameters must be bound on the query builder that executes the statement.
 */
final readonly class Exists implements Expression
{
    public function __construct(
        private QueryBuilder $subQuery,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        return sprintf(
            'EXISTS (%s)',
            $this->subQuery->getSQL(),
        );
    }
}
