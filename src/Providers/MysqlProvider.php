<?php
declare(strict_types=1);

namespace Noldors\Queues\Providers;

use Noldors\Queues\Contracts\ProviderInterface;

class MysqlProvider extends DatabaseProvider implements ProviderInterface
{

    public function __construct(
        $databaseName,
        $userName,
        $password,
        $prefix = '',
        $host = 'localhost',
        $port = 3306,
        $charset = 'utf8mb64',
        $collation = 'utf8mb4_unicode_ci'
    ) {

    }

    /**
     * Check if queues table exists.
     *
     * @return bool
     */
    protected function tableExists()
    {
        $tableExist = $this->pdo->query("SELECT * FROM information_schema.tables WHERE table_schema = 'yourdb' AND table_name = 'testtable' LIMIT 1;")->fetchColumn();

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
        CREATE TABLE queues
        (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          handler TEXT NOT NULL,
          data TEXT,
          status INTEGER NOT NULL
        );
        ");

        $this->pdo->query("CREATE INDEX status ON queues (status);");
    }
}