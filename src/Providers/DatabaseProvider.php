<?php
declare(strict_types=1);

namespace Noldors\Queues\Providers;

use Noldors\Queues\Exceptions\ClassNotFoundException;
use Noldors\Queues\Exceptions\MethodNotFoundException;
use Noldors\Queues\Exceptions\NotClassMethodCallbackException;

abstract class DatabaseProvider
{
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
        $queue = $this->pdo->prepare("INSERT INTO {$this->tableName} (handler, data, status) VALUES (:handler, :data, 0)");
        $queue->bindParam(':handler', $handler);
        $queue->bindParam(':data', json_encode($data));

        return $queue->execute();
    }

    /**
     * Get all new queues.
     *
     * @return array
     */
    private function getQueues()
    {
        return $this->pdo->query("SELECT id, handler, data FROM {$this->tableName} WHERE status = 0 ORDER BY id ASC")->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Execute all queues.
     *
     * @return void
     */
    public function execute()
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
     */
    private function executeQueue(array $queue)
    {
        $data = json_decode($queue['data'], true);

        $handler = $this->parseCallback($queue['handler']);

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
    protected function setExecutionStatus(int $id)
    {
        $queue = $this->pdo->prepare("UPDATE {$this->tableName} SET status = 1 WHERE id = ?");

        return $queue->execute([$id]);
    }

    /**
     * Parse handler string to get class and method.
     *
     * @param string $handler
     *
     * @return array
     * @throws \Noldors\Queues\Exceptions\NotClassMethodCallbackException
     */
    protected function parseCallback(string $handler)
    {
        if (mb_stripos($handler, '@', 0, 'UTF-8') !== false) {
            [$class, $method] = explode('@', $handler);

            return ['class' => $class, 'method' => $method];
        }

        if (mb_stripos($handler, '::', 0, 'UTF-8') !== false) {
            [$class, $method] = explode('::', $handler);

            return ['class' => $class, 'method' => $method];
        }

        throw new NotClassMethodCallbackException('Handler must be like \Namespace\ClassName::methodName for static methods, \Namespace\ClassName@methodName namespace not required.');
    }

    /**
     * Determine that handler in right format
     *
     * @param string $handler
     *
     * @return bool
     * @throws \Noldors\Queues\Exceptions\NotClassMethodCallbackException
     */
    protected function checkCallback(string $handler)
    {
        if (
            (mb_stripos($handler, '@', 0, 'UTF-8') === false) &&
            (mb_stripos($handler, '::', 0, 'UTF-8') === false)
        ) {
            throw new NotClassMethodCallbackException('Handler must be like \Namespace\ClassName::methodName for static methods, \Namespace\ClassName@methodName namespace not required.');
        }

        return true;
    }
}