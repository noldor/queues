<?php
declare(strict_types=1);

namespace Noldors\Queues\Contracts;

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
    public function push(string $handler, array $data):bool;

    /**
     * Execute all queues.
     *
     * @return void
     */
    public function execute();
}