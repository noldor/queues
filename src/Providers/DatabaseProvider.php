<?php
declare(strict_types=1);

namespace Noldors\Queues\Providers;

use Noldors\Helpers\Str;
use Noldors\Queues\Exceptions\ClassNotFoundException;
use Noldors\Queues\Exceptions\MethodNotFoundException;
use Noldors\Queues\Exceptions\NotClassMethodCallbackException;

/**
 * Abstract class for database providers.
 *
 * @package Noldors\Queues\Providers
 */
abstract class DatabaseProvider
{
    /**
     * Table name without prefix.
     */
    public const TABLE_NAME = 'queues';

    /**
     * Pdo instance.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Table name.
     *
     * @var string
     */
    protected $tableName;

    /**
     * Add new queue.
     *
     * @param string $handler
     * @param array  $data
     *
     * @return bool
     */
    public function push(string $handler, array $data): bool
    {
        $this->checkCallback($handler);
        $queue = $this->pdo->prepare("INSERT INTO {$this->tableName} (handler, data, status) VALUES (:handler, :data, :status)");
        $queue->bindValue(':handler', $handler, \PDO::PARAM_STR);
        $queue->bindValue(':data', json_encode($data), \PDO::PARAM_STR);
        $queue->bindValue(':status', false, \PDO::PARAM_BOOL);

        return $queue->execute();
    }

    /**
     * Get all new queues.
     *
     * @return array
     */
    protected function getQueues(): array
    {
        $queues = $this->pdo->prepare("SELECT id, handler, data FROM {$this->tableName} WHERE status = :status ORDER BY id ASC");
        $queues->bindValue(':status', false, \PDO::PARAM_BOOL);
        $queues->execute();

        return $queues->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Execute all queues.
     *
     * @return void
     */
    public function execute(): void
    {
        foreach ($this->getQueues() as $queue) {
            $this->executeQueue($queue);
        }
    }

    /**
     * Execute single queue.
     *
     * @param array $queue
     *
     * @throws \Noldors\Queues\Exceptions\ClassNotFoundException
     * @throws \Noldors\Queues\Exceptions\MethodNotFoundException
     *
     * @return void
     */
    protected function executeQueue(array $queue): void
    {
        $data = json_decode($queue['data'], true);

        $this->checkCallback($queue['handler']);
        $handler = (new Str($queue['handler']))->parseCallback();

        if (!class_exists($handler['class'])) {
            throw new ClassNotFoundException("Class {$handler['class']} not found");
        }

        if (!method_exists($handler['class'], $handler['method'])) {
            throw new MethodNotFoundException("Method {$handler['method']} not found in class {$handler['class']}");
        }

        try {
            $this->pdo->beginTransaction();
            call_user_func_array([$handler['class'], $handler['method']], $data);
            $this->setExecutionStatus((int)$queue['id']);
            $this->pdo->commit();
        } catch (\Exception $exception) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Update execution status.
     *
     * @param int $id
     *
     * @return bool
     */
    protected function setExecutionStatus(int $id): bool
    {
        $queue = $this->pdo->prepare("UPDATE {$this->tableName} SET status = :status WHERE id = :id");
        $queue->bindValue(':status', true, \PDO::PARAM_BOOL);
        $queue->bindValue(':id', $id, \PDO::PARAM_INT);

        return $queue->execute();
    }

    /**
     * Determine that handler in right format
     *
     * @param string $handler
     *
     * @return bool
     * @throws \Noldors\Queues\Exceptions\NotClassMethodCallbackException
     */
    protected function checkCallback(string $handler): bool
    {
        if (!(new Str($handler))->checkCallback()) {
            throw new NotClassMethodCallbackException('Handler must be like \Namespace\ClassName::methodName for static methods, \Namespace\ClassName@methodName namespace not required.');
        }

        return true;
    }
}
