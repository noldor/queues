<?php
declare(strict_types=1);

namespace Noldors\Queues\Providers;

use Noldors\Queues\Contracts\ProviderInterface;
use Noldors\Queues\Exceptions\NoDatabaseFoundException;
use Noldors\Queues\Exceptions\ProviderExtensionNotInstalledException;

/**
 * Store all queues in Sqlite database.
 *
 * @package Noldors\Queues\Providers
 */
class SqliteProvider extends DatabaseProvider implements ProviderInterface
{
    /**
     * SqliteProvider constructor.
     *
     * @param string $pathToDatabase
     * @param string $prefix
     *
     * @throws \Noldors\Queues\Exceptions\NoDatabaseFoundException
     * @throws \Noldors\Queues\Exceptions\ProviderExtensionNotInstalledException
     */
    public function __construct(string $pathToDatabase, $prefix = '')
    {
        if (!file_exists($pathToDatabase)) {
            throw new NoDatabaseFoundException("There are no sqlite database in {$pathToDatabase}");
        }

        if (!extension_loaded('pdo_sqlite')) {
            throw new ProviderExtensionNotInstalledException('pdo_sqlite extension not loaded or installed');
        }

        $this->pdo = new \PDO("sqlite:{$pathToDatabase}");
        $this->tableName = $prefix . static::TABLE_NAME;

        if (!$this->tableExists()) {
            $this->createTable();
        }
    }

    /**
     * Check if queues table exists.
     *
     * @return bool
     */
    protected function tableExists(): bool
    {
        $tableExist = $this->pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='{$this->tableName}'")->fetchColumn();

        return ($tableExist === false) ? false : true;
    }

    /**
     * Create queues table.
     *
     * @return void
     */
    protected function createTable(): void
    {
        $this->pdo->query("
        CREATE TABLE {$this->tableName}
        (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          handler TEXT NOT NULL,
          data TEXT,
          status INTEGER NOT NULL
        );
        ");

        $this->pdo->query("CREATE INDEX status ON {$this->tableName} (status);");
    }
}
