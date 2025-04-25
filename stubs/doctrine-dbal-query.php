<?php

namespace Doctrine\DBAL\Query;

class QueryBuilder
{
    /**
     * @see QueryBuilder::join
     *
     * @param array{fromAlias: string, join: string, alias: string, condition: string|null} $args
     */
    public function join(string ...$args): QueryBuilder;
    // public function join(string $fromAlias, string $join, string $alias, ?string $condition = null): self

    /**
     * @see QueryBuilder::innerJoin
     *
     * @param array{fromAlias: string, join: string, alias: string, condition: string|null} $args
     */
    public function innerJoin(string ...$args): QueryBuilder;
    // public function innerJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self

    /**
     * @see QueryBuilder::leftJoin
     *
     * @param array{fromAlias: string, join: string, alias: string, condition: string|null} $args
     */
    public function leftJoin(string ...$args): QueryBuilder;
    // public function leftJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self

    /**
     * @see QueryBuilder::rightJoin()
     *
     * @param array{fromAlias: string, join: string, alias: string, condition: string|null} $args
     */
    public function rightJoin(string ...$args): QueryBuilder;
    // public function rightJoin(string $fromAlias, string $join, string $alias, ?string $condition = null): self
}
