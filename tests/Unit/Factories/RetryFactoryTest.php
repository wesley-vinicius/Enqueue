<?php

namespace Unit\Factories;

use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\Factories\RetryFactory;
use Wesley\Enqueue\Retry\Retry;
use Wesley\Enqueue\Retry\RetryItem;

class RetryFactoryTest extends TestCase
{
    public function testItMustCreateRetry()
    {
        $factory = new RetryFactory();

        $retry = $factory->create('test/test', [
            'retry' => [
                [
                    'name' => 'retry-1',
                    'queue' => 'retry-1',
                    'interval' => 60,
                    'attempts' => 3
                ]
            ]
        ]);

        $retryConfig = $retry->config();
        self::assertInstanceOf(Retry::class, $retry);
        self::assertCount(1, $retryConfig->items());
        self::assertInstanceOf(RetryItem::class, $retryConfig->items()[0]);
        self::assertEquals('test/test', $retryConfig->items()[0]->commandRetry());
    }
}
