<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Expression;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;

/**
 * @psalm-type ValueExpressionProvider = \Closure(string): Expression
 */
final readonly class Search implements Expression
{
    /**
     * @param ValueExpressionProvider $likeValueExpressionProvider
     */
    public function __construct(
        private Expressions $expressions,
        private string $searchQuery,
        private \Closure $likeValueExpressionProvider,
    ) {
    }

    /**
     * @return ValueExpressionProvider
     */
    public static function queryBuilderPlaceholderValueProvider(
        QueryBuilder $queryBuilder,
        ?string $placeholderName = null,
    ): \Closure {
        return static fn (string $searchValue) => SqlExpression::parameter(
            $queryBuilder->createNamedParameter($searchValue, Types::STRING, $placeholderName)
        );
    }

    /**
     * @return non-empty-string
     */
    public function toSQL(): string
    {
        $likeValue = ($this->likeValueExpressionProvider)('%'.$this->searchQuery.'%');

        return Composite::or(
            ...$this->expressions->traverse(
                static fn (Expression $expression): Expression => new ILike($expression, $likeValue)
            )
        )->toSQL();
    }
}
