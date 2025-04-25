[![Installs](https://img.shields.io/packagist/dt/phpro/dbal-tools.svg)](https://packagist.org/packages/phpro/dbal-tools/stats)
[![Packagist](https://img.shields.io/packagist/v/phpro/dbal-tools.svg)](https://packagist.org/packages/phpro/api-problem-bundle)


# DBAL Tools

This package provides a set of tools to work with the Doctrine DBAL.

## Installation

```sh
composer require phpro/dbal-tools
```

The package can be used standalone or with Symfony.
If you are not using `symfony/flex`, you'll have to manually add the bundle to your bundles file:

```php
// config/bundles.php

return [
    // ...
    Phpro\DbalTools\DbalToolsBundle::class => ['all' => true],
];
```

## Schema

This package contains a set of schema tools to configure your database schema from within PHP.
You can configure following schema types:

### Configuring a table

A table consists out of 2 parts:

- The class that declared the table
- An enum that declares the available table columns:

```php
use Phpro\DbalTools\Column\TableColumnsInterface;
use Phpro\DbalTools\Column\TableColumnsTrait;

enum UsersTableColumns: string implements TableColumnsInterface
{
    case Id = 'user_id';
    case Username = 'username';

    use TableColumnsTrait;

    public function linkedTableClass(): string
    {
        return UsersTable::class;
    }
}
```

```php
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Types\Types;
use Phpro\DbalTools\Column\Columns;
use Phpro\DbalTools\Schema\Table;

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

        return $table;
    }
}
```

You can register this table configuration in your Symfony service configuration:

```yaml
services:
    App\Dbal\Schema\UsersTable:
        tags:
            - 'phpro.dbal_tools.schema.table'
```

This way, it is available inside the `Doctrine\DBAL\Schema\Schema` service and can be used to create the table through migrations.

### Configuring sequences

From time to time, you might need a sequence.
This can be configured in the same way as a table:

```php
use Phpro\DbalTools\Schema\Sequence;
use Doctrine\DBAL\Schema\Sequence as DoctrineSequence;

final class UserNumberSequence extends Sequence
{
    public static function name(): string
    {
        return 'user_number_seq';
    }

    public static function createSequence(): DoctrineSequence
    {
        return new DoctrineSequence(self::name());
    }
}
```

Next, add the sequence to your service configuration:

```yaml
services:
    App\Dbal\Schema\UserNumberSequence:
        tags:
            - 'phpro.dbal_tools.schema.sequence'
```

## Migrations

## Fixtures

## Testing

## About

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/phpro/dbal-tools/issues).
Please take a look at our rules before [contributing your code](CONTRIBUTING).

### License

api-problem-bundle is licensed under the [MIT License](LICENSE).
