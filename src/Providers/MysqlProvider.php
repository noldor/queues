<?php
declare(strict_types=1);

namespace Noldors\Queues\Providers;

use Noldors\Queues\Contracts\ProviderInterface;
use Noldors\Queues\Exceptions\ProviderExtensionNotInstalledException;

/**
 * Store all queues in mysql database.
 *
 * @package Noldors\Queues\Providers
 */
class MysqlProvider extends DatabaseProvider implements ProviderInterface
{
    /**
     * Database name.
     *
     * @var string
     */
    protected $databaseName;

    /**
     * MysqlProvider constructor.
     *
     * @param string      $databaseName
     * @param string      $userName
     * @param string      $password
     * @param string      $prefix
     * @param string|null $unixSocket
     * @param string      $host
     * @param int         $port
     * @param string      $charset
     * @param string      $collation
     * @param bool        $strict
     * @param array|null  $modes
     *
     * @throws \Noldors\Queues\Exceptions\ProviderExtensionNotInstalledException
     */
    public function __construct(
        string $databaseName,
        string $userName,
        string $password,
        string $prefix = '',
        string $unixSocket = null,
        string $host = '127.0.0.1',
        int $port = 3306,
        string $charset = 'utf8',
        string $collation = 'utf8_unicode_ci',
        bool $strict = true,
        array $modes = null
    ) {
        if (!extension_loaded('pdo_mysql')) {
            throw new ProviderExtensionNotInstalledException('pdo_mysql extension not loaded or installed');
        }

        $this->tableName = $prefix . static::TABLE_NAME;
        $this->databaseName = $databaseName;

        if (!is_null($unixSocket)) {
            $this->pdo = new \PDO("mysql:unix_socket={$host};dbname={$databaseName}", $userName, $password);
        } else {
            $this->pdo = new \PDO("mysql:host={$host};port={$port};dbname={$databaseName}", $userName, $password);
        }

        $this->pdo->exec("use {$databaseName}");
        $this->pdo->exec("SET NAMES {$charset} COLLATE {$collation}");
        $this->setModes($modes, $strict);

        if (!$this->tableExists()) {
            $this->createTable();
        }
    }

    /**
     * Set mysql connection mode.
     *
     * @param array|null $modes
     * @param            $strict
     */
    private function setModes(?array $modes, bool $strict)
    {
        if (!is_null($modes)) {
            $this->setCustomModes($modes);
        } else {
            if ($strict) {
                $this->setStrictMode();
            } else {
                $this->pdo->exec("set session sql_mode='NO_ENGINE_SUBSTITUTION'");
            }
        }
    }

    /**
     * Set user specified modes.
     *
     * @param array $modes
     */
    private function setCustomModes(array $modes)
    {
        $modes = implode(',', $modes);

        $this->pdo->exec("set session sql_mode='{$modes}'");
    }

    /**
     * Declare strict mode for connection.
     *
     * @return void
     */
    private function setStrictMode(): void
    {
        $this->pdo->exec("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
    }

    /**
     * Check if queues table exists.
     *
     * @return bool
     */
    protected function tableExists()
    {
        $tableExist = $this->pdo->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = '{$this->databaseName}' AND `table_name` = '{$this->tableName}'")->fetchColumn();

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
          id INT PRIMARY KEY AUTO_INCREMENT,
          handler VARCHAR(255) NOT NULL,
          data TEXT,
          status BOOLEAN NOT NULL
        );
        ");

        $this->pdo->query("CREATE INDEX `status` ON queues (`status`);");
    }
}