<?php

namespace Unit\Factories;

use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\Producer;
use Wesley\Enqueue\Factories\ProducerKafkaFactory;

class ProducerKafkaFactoryTest extends TestCase
{
    public function testItMustCreateInstanceEnqueueProducer()
    {
        $config = [
            'global' => [
                'group.id' => 'test',
                'metadata.broker.list' => 'localhost:9092',
            ],
        ];

        $factory = new ProducerKafkaFactory();
        $enqueueProducer = $factory->create($config);

        self::assertInstanceOf(Producer::class, $enqueueProducer);
    }
}
