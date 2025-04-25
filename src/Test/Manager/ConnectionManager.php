<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test\Manager;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\DefaultSchemaManagerFactory;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\TransactionIsolationLevel;

final class ConnectionManager
{
    /**
     * @psalm-suppress InvalidArgument - Unable to infer createConnection parameters.
     */
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
            $dbName = $connectionParams['dbname'].$dbSuffix;

            // Try creating database
            $preConnection = $connectionFactory->createConnection($connectionParams, $dbalConfig);
            $schemaManager = $preConnection->createSchemaManager();
            if (in_array($dbName, $schemaManager->listDatabases(), true)) {
                $schemaManager->dropDatabase($dbName);
            }
            $schemaManager->createDatabase($dbName);
            $preConnection->close();

            // Connect to test db
            $connection = $connectionFactory->createConnection([...$connectionParams, 'dbname_suffix' => $dbSuffix], $dbalConfig);
            $connection->setAutoCommit(false);
            $connection->setTransactionIsolation(TransactionIsolationLevel::READ_UNCOMMITTED);

            return $connection;
        })();

        /** @var Connection */
        return $connection;
    }
}
