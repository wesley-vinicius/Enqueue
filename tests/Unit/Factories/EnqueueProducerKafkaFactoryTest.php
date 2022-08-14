<?php

namespace Unit\Factories;

use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\EnqueueProducer;
use Wesley\Enqueue\Factories\EnqueueProducerKafkaFactory;

class EnqueueProducerKafkaFactoryTest extends TestCase
{
    public function testItMustCreateInstanceEnqueueProducer()
    {
        $config = [
            'global' => [
                'group.id' => 'test',
                'metadata.broker.list' => 'localhost:9092',
            ],
        ];

        $factory = new EnqueueProducerKafkaFactory();
        $enqueueProducer = $factory->create($config);

        self::assertInstanceOf(EnqueueProducer::class, $enqueueProducer);
    }
}
