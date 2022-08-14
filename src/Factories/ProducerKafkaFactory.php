<?php

namespace Wesley\Enqueue\Factories;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Wesley\Enqueue\Producer;

class ProducerKafkaFactory
{
    public function create(array $config): Producer
    {
        $connectionFactory = new RdKafkaConnectionFactory($config);

        return new Producer($connectionFactory);
    }
}
