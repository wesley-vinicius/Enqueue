<?php

namespace Unit\Factories;

use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\Consumer;
use Wesley\Enqueue\Factories\ConsumerKafkaFactory;

class ConsumerKafkaFactoryTest extends TestCase
{
    public function testItMustCreateInstanceConsumer()
    {
        $config = [
            'global' => [
                'group.id' => 'test',
                'metadata.broker.list' => 'localhost:9092',
            ],
        ];

        $factory = new ConsumerKafkaFactory();
        $consumer = $factory->create($config);

        self::assertInstanceOf(Consumer::class, $consumer);
    }
}
