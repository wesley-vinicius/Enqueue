<?php

namespace Wesley\Enqueue\Factories;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Wesley\Enqueue\EnqueueProducer;

class EnqueueProducerKafkaFactory
{
    public function create(array $config): EnqueueProducer
    {
        $connectionFactory = new RdKafkaConnectionFactory($config);

        return new EnqueueProducer($connectionFactory);
    }
}
