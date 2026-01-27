<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test\Manager;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\TransactionIsolationLevel;
use function Psl\Type\string;
use function Psl\Vec\map;

/**
 * @psalm-suppress InternalClass - ConnectionFactory us marked as internal nowadays - we'll take the risk.
 * @psalm-suppress UnusedPsalmSuppress - ConnectionFactory is not marked as internal for all doctrine-bundle versions.
 */
final class ConnectionManager
{
    public static function getConnection(): Connection
    {
        static $connection = (static function (): Connection {
            $connectionParams = [
                ...(new DsnParser(['postgresql' => 'pdo_pgsql']))->parse($_ENV['DATABASE_URL'] ?? ''),
                'driverOptions' => [
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ];

            $dbalConfig = new Configuration();
            $dbalConfig->setSchemaManagerFactory(new DefaultSchemaManagerFactory());

            $connectionFactory = new ConnectionFactory([]);

            // Use unique suffix for paratest:
            $dbSuffix = '_test'.((string) getenv('TEST_TOKEN'));
            $dbName = string()->assert($connectionParams['dbname']).$dbSuffix;

            // Try creating database
            /**
             * @psalm-suppress InvalidArgument - DsnParser returns array<string, mixed> which cannot be narrowed to the expected shape
             * @psalm-suppress UnusedPsalmSuppress - Only needed for older doctrine-bundle versions
             */
            $preConnection = $connectionFactory->createConnection($connectionParams, $dbalConfig);
            $schemaManager = $preConnection->createSchemaManager();
            $existingDatabases = map(
                $schemaManager->introspectDatabaseNames(),
                static fn (UnqualifiedName $name): string => $name->getIdentifier()->getValue(),
            );
            if (in_array($dbName, $existingDatabases, true)) {
                $schemaManager->dropDatabase($dbName);
            }
            $schemaManager->createDatabase($dbName);
            $preConnection->close();

            // Connect to test db
            /**
             * @psalm-suppress InvalidArgument - DsnParser returns array<string, mixed> which cannot be narrowed to the expected shape
             * @psalm-suppress UnusedPsalmSuppress - Only needed for older doctrine-bundle versions
             */
            $connection = $connectionFactory->createConnection([...$connectionParams, 'dbname_suffix' => $dbSuffix], $dbalConfig);
            $connection->setAutoCommit(false);
            $connection->setTransactionIsolation(TransactionIsolationLevel::READ_UNCOMMITTED);

            return $connection;
        })();

        /** @var Connection */
        return $connection;
    }
}
