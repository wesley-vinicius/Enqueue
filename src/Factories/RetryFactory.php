<?php

namespace Wesley\Enqueue\Factories;

use Wesley\Enqueue\Retry\Retry;
use Wesley\Enqueue\Retry\RetryConfig;

class RetryFactory
{
    public function create( string $commandRetry, array $config): Retry
    {
        return new Retry(
            (new ProducerKafkaFactory())->create($config),
            RetryConfig::fromArray($commandRetry, $config['retry']),
        );
    }
}
