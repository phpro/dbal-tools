<?php

declare(strict_types=1);

namespace PhproTest\DbalTools\Fixtures\Schema;

use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\Types;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Schema\Table;

/**
 * @psalm-import-type JoinInfo from Table
 */
final class UsersTable extends Table
{
    public static function name(): string
    {
        return 'users';
    }

    public static function columns(): Columns
    {
        return Columns::for(UsersTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        $table = new DoctrineTable(self::name());
        $table->addColumn(UsersTableColumns::Id->value, Types::GUID, [
            'notnull' => true,
        ]);
        $table->setPrimaryKey([UsersTableColumns::Id->value]);

        $table->addColumn(UsersTableColumns::Username->value, Types::STRING, [
            'length' => 255,
            'notnull' => true,
        ]);
        $table->addUniqueIndex(
            [UsersTableColumns::Username->value],
            'uniq_username',
        );

        $table->addColumn(UsersTableColumns::FirstName->value, Types::STRING, [
            'length' => 255,
            'notnull' => true,
        ]);

        $table->addColumn(UsersTableColumns::LastName->value, Types::STRING, [
            'length' => 255,
            'notnull' => true,
        ]);

        return $table;
    }

    /**
     * @param non-empty-string|null $fromAlias
     * @param non-empty-string|null $asAlias
     *
     * @return JoinInfo
     */
    public static function joinOntoPosts(?string $fromAlias = null, ?string $asAlias = null): array
    {
        $from = $fromAlias ?? self::name();
        $as = $asAlias ?? PostsTable::name();

        return [
            'fromAlias' => $from,
            'join' => PostsTable::name(),
            'alias' => $as,
            'condition' => sprintf(
                '%s = %s',
                PostsTableColumns::UserId->onTable($from)->use(),
                UsersTableColumns::Id->onTable($as)->use(),
            ),
        ];
    }
}
