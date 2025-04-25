<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Pagerfanta;

use Doctrine\DBAL\Query\QueryBuilder;
use Pagerfanta\Doctrine\DBAL\QueryAdapter;
use Phpro\DbalTools\Column\Column;
use Phpro\DbalTools\Expression\Count;

final readonly class QueryAdapterFactory
{
    public static function create(QueryBuilder $query, Column $countColumn): QueryAdapter
    {
        return new QueryAdapter(
            $query,
            static fn (QueryBuilder $queryBuilder) => $queryBuilder
                ->resetGroupBy()
                ->select($countColumn->apply(
                    static fn ($col) => new Count($col),
                    'total_results'
                ))
                ->orderBy('total_results')
                ->setMaxResults(1)
        );
    }
}
