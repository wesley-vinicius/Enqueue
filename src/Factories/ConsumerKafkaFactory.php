<?php

namespace Wesley\Enqueue\Factories;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Wesley\Enqueue\Consumer;

class ConsumerKafkaFactory
{
    public function create(array $config): Consumer
    {
        $connectionFactory = new RdKafkaConnectionFactory($config);

        return new Consumer($connectionFactory, $config);
    }
}
