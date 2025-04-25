<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Test\Manager;

use Doctrine\DBAL\Connection;

final readonly class TransactionManager
{
    public static function instance(): self
    {
        static $connection = ConnectionManager::getConnection();

        return new self($connection);
    }

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function createSavepoint(string $savepoint): void
    {
        if (!$this->connection->isTransactionActive()) {
            $this->connection->beginTransaction();
        }
        $this->connection->createSavepoint($savepoint);
    }

    public function rollbackSavepoint(string $savepoint): void
    {
        $this->connection->rollbackSavepoint($savepoint);
    }
}
