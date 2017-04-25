<?php
declare(strict_types=1);

namespace Noldors\Queues\Providers;

use Noldors\Queues\Exceptions\ProviderExtensionNotInstalledException;

/**
 * Store all queues in PostgreSQL database.
 * @package Noldors\Queues\Providers
 */
class PostgresProvider extends DatabaseProvider
{
    /**
     * Database name.
     *
     * @var string
     */
    protected $databaseName;

    /**
     * PostgresProvider constructor.
     *
     * @param string      $databaseName
     * @param string      $userName
     * @param string      $password
     * @param string      $prefix
     * @param string|null $host
     * @param int|null    $port
     * @param string      $charset
     * @param string      $schema
     * @param array       $ssl
     *
     * @throws \Noldors\Queues\Exceptions\ProviderExtensionNotInstalledException
     */
    public function __construct(
        string $databaseName,
        string $userName,
        string $password,
        string $prefix = '',
        string $host = null,
        int $port = null,
        string $charset = 'utf8',
        $schema = 'public',
        array $ssl = ['prefer']
    ) {
        if (!extension_loaded('pdo_pgsql')) {
            throw new ProviderExtensionNotInstalledException('pdo_pgsql extension not loaded or installed');
        }

        $this->tableName = $prefix . static::TABLE_NAME;
        $this->databaseName = $databaseName;

        $this->pdo = new \PDO($this->getDsn($host, $port, $ssl), $userName, $password);

        $this->pdo->exec("set names {$charset}");
        $this->setSchema($schema);

        if (!$this->tableExists()) {
            $this->createTable();
        }
    }

    /**
     * Build dsn for connection.
     *
     * @param null|string $host
     * @param int|null    $port
     * @param array|null  $ssl
     *
     * @return string
     */
    private function getDsn(?string $host, ?int $port, ?array $ssl)
    {
        $host = !is_null($host) ? "host={$host};" : '';

        $port = !is_null($port) ? ";port={$port}" : '';

        $dsn = "pgsql:{$host}dbname={$this->databaseName}{$port}";

        foreach (['sslmode', 'sslcert', 'sslkey', 'sslrootcert'] as $option) {
            if (isset($ssl[$option])) {
                $dsn .= ";{$option}={$ssl[$option]}";
            }
        }

        return $dsn;
    }

    /**
     * Set schema for connection.
     *
     * @param array|string|null $schema
     *
     * @return string
     */
    private function setSchema($schema)
    {
        if (!is_null($schema)) {
            if (is_array($schema)) {
                $schema = '"'.implode('", "', $schema).'"';
            } else {
                return '"'.$schema.'"';
            }

            $this->pdo->exec("set search_path to {$schema}");
        }
    }

    /**
     * Check if queues table exists.
     *
     * @return bool
     */
    protected function tableExists()
    {
        $tableExist = $this->pdo->query("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE  table_schema = '{$this->databaseName}' AND table_name = '{$this->tableName}');")->fetchColumn();

        return ((int)$tableExist === 0) ? false : true;
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
          id SERIAL PRIMARY KEY,
          handler VARCHAR(255) NOT NULL,
          data JSON,
          status BOOL NOT NULL
        );
        ");

        $this->pdo->query("CREATE INDEX status ON queues (status)");
    }
}