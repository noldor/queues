<?php
declare(strict_types=1);

namespace Noldors\Queues\Contracts;

/**
 * Interface for queues storage.
 *
 * @package Noldors\Queues\Contracts
 */
interface ProviderInterface
{
    /**
     * Add new queue.
     *
     * @param string $handler
     * @param array  $data
     *
     * @return bool
     */
    public function push(string $handler, array $data): bool;

    /**
     * Execute all new queues.
     *
     * @return void
     */
    public function execute(): void;
}