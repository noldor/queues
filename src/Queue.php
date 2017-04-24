<?php
declare(strict_types=1);

namespace Noldor\Queues;

use Noldors\Queues\ProviderInterface;

class Queue
{
    private $provider;

    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public static function make(ProviderInterface $provider): self
    {
        return new static($provider);
    }

    public function push(string $handler, iterable $data)
    {
        $this->provider->push($handler, $data);
    }
}