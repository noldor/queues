<?php
declare(strict_types=1);

namespace Noldors\Queues;

use Noldors\Queues\Contracts\ProviderInterface;

/**
 * Class Queue
 * @package Noldor\Queues
 */
class Queue
{
    private $provider;

    /**
     * Queue constructor.
     *
     * @param \Noldors\Queues\Contracts\ProviderInterface $provider
     */
    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Static constructor.
     *
     * @param \Noldors\Queues\Contracts\ProviderInterface $provider
     *
     * @return self
     */
    public static function make(ProviderInterface $provider): self
    {
        return new static($provider);
    }

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
        return $this->provider->push($handler, $data);
    }

    /**
     * Execute all new queues.
     *
     * @return void
     */
    public function execute(): void
    {
        $this->provider->execute();
    }
}
