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
final class PostsTable extends Table
{
    public static function name(): string
    {
        return 'posts';
    }

    public static function columns(): Columns
    {
        return Columns::for(PostsTableColumns::class);
    }

    public static function createTable(): DoctrineTable
    {
        $table = new DoctrineTable(self::name());
        $table->addColumn(PostsTableColumns::Id->value, Types::GUID, [
            'notnull' => true,
        ]);
        $table->setPrimaryKey([PostsTableColumns::Id->value]);

        $table->addColumn(PostsTableColumns::UserId->value, Types::GUID, [
            'notnull' => true,
        ]);
        $table->addForeignKeyConstraint(
            foreignTableName: UsersTable::name(),
            localColumnNames: [PostsTableColumns::UserId->value],
            foreignColumnNames: [UsersTableColumns::Id->value],
            options: [
                'onDelete' => 'CASCADE',
            ],
            name: 'fk_user_id',
        );

        $table->addColumn(PostsTableColumns::Post->value, Types::STRING, [
            'length' => 255,
            'notnull' => true,
        ]);

        return $table;
    }
}
