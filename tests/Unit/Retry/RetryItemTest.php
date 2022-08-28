<?php

namespace Unit\Retry;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\Retry\RetryAttempt;
use Wesley\Enqueue\Retry\RetryItem;

class RetryItemTest extends TestCase
{
    public function testItMustCreateInstanceRetryItem()
    {
        $retryItem = new RetryItem(
            commandRetry: 'Test/Class',
            name: 'test',
            queue: 'test-queue',
            interval: 60,
            attempts: 1,
        );

        self::assertInstanceOf(RetryItem::class, $retryItem);
        self::assertEquals('Test/Class', $retryItem->commandRetry());
        self::assertEquals('test', $retryItem->name());
        self::assertEquals('test-queue', $retryItem->queue());
        self::assertEquals(60, $retryItem->interval());
        self::assertEquals(1, $retryItem->attempts());
    }

    public function testItMustAddCurrentAttempt()
    {
        $retryItem = $this->createRetryItem();
        $retryItem->addCurrentAttempt(1);

        self::assertEquals(1, $retryItem->currentAttempt());
    }

    public function testItMustReturnInstanceRetryAttempt()
    {
        $retryItem = $this->createRetryItem();
        self::assertInstanceOf(RetryAttempt::class, $retryItem->nextAttempt());
    }

    public function testItMustReturnNullWhenNoNextRetryAttempt()
    {
        $retryItem = $this->createRetryItem()
            ->addCurrentAttempt(1);

        self::assertNull($retryItem->nextAttempt());
    }

    /**
     * @dataProvider dataInvalid()
     */
    public function testItMustThrowExceptionWhenInformedDataInvalid(string $commandRetry, string $name, string $queue)
    {
        self::expectException(InvalidArgumentException::class);

        new RetryItem(
            commandRetry: $commandRetry,
            name: $name,
            queue: $queue,
            interval: 60,
            attempts: 1,
        );
    }

    private function createRetryItem( ): RetryItem {
        return new RetryItem(
            commandRetry: 'Test/Class',
            name: 'test',
            queue: 'test-queue',
            interval: 60,
            attempts: 1,
        );
    }

    public function dataInvalid(): array
    {
        return [
            ['', 'test', 'test-queue'],
            ['test/class', '', 'test-queue'],
            ['test/class', 'test', ''],
        ];
    }
}
