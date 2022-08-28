<?php

namespace Unit\Retry;

use Interop\Queue\Message;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\Retry\RetryAttempt;
use Wesley\Enqueue\Retry\RetryConfig;
use Wesley\Enqueue\Retry\RetryItem;

class RetryConfigTest extends TestCase
{
    private Message $messageMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->messageMock = $this->createMock(Message::class);
    }

    public function testItMustCreateRetryConfig()
    {
        $config = [
            [
                'name' => 'retry-1',
                'queue' => 'retry-1',
                'interval' => 60,
                'attempts' => 3
            ],
        ];

        $retryConfig = RetryConfig::fromArray('test', $config);
        self::assertInstanceOf(RetryConfig::class, $retryConfig);
    }

    public function testItMustReturnFirstRetryAttempt()
    {
        $this->messageMock->expects($this->once())
            ->method('getHeader')
            ->with('retry_name')
            ->willReturn('');

        $retryConfig = new RetryConfig(
            ...[$this->createRetryItem('retry-1'), $this->createRetryItem('retry-2')]
        );
        $retryItem = $retryConfig->nextRetry($this->messageMock);

        self::assertInstanceOf(RetryAttempt::class, $retryItem);
        self::assertEquals('retry-1', $retryItem->name());
    }

    public function testItMustReturnNextInCurrentRetryAttempt()
    {
        $this->messageMock->expects($this->exactly(3))
            ->method('getHeader')
            ->withConsecutive(['retry_name'], ['retry_name'], ['retry_attempt'])
            ->willReturnOnConsecutiveCalls('retry-2','retry-2', 1);

        $retryConfig = new RetryConfig(
            ...[$this->createRetryItem('retry-2'), $this->createRetryItem('retry-3')]
        );
        $retryItem = $retryConfig->nextRetry($this->messageMock);

        self::assertInstanceOf(RetryAttempt::class, $retryItem);
        self::assertEquals('retry-2', $retryItem->name());
        self::assertEquals(2, $retryItem->attempt());
    }

    public function testItMustThrowExceptionNoFindRetryByName()
    {
        self::expectException(InvalidArgumentException::class);

        $this->messageMock->expects($this->exactly(3))
            ->method('getHeader')
            ->withConsecutive(['retry_name'], ['retry_name'], ['retry_name'], ['retry_attempt'])
            ->willReturnOnConsecutiveCalls('retry-2', 'retry-2', 'retry-2', 1);

        $retryConfig = new RetryConfig(
            ...[$this->createRetryItem('retry-1'), $this->createRetryItem('retry-3')]
        );
        $retryConfig->nextRetry($this->messageMock);
    }

    public function testItMustReturnNextRetryAttempt()
    {
        $this->messageMock->expects($this->exactly(3))
            ->method('getHeader')
            ->withConsecutive(['retry_name'], ['retry_name'], ['retry_attempt'])
            ->willReturnOnConsecutiveCalls('retry-2','retry-2', 3);

        $retryConfig = new RetryConfig(
            ...[$this->createRetryItem('retry-2'), $this->createRetryItem('retry-3')]
        );
        $retryItem = $retryConfig->nextRetry($this->messageMock);

        self::assertInstanceOf(RetryAttempt::class, $retryItem);
        self::assertEquals('retry-3', $retryItem->name());
        self::assertEquals(1, $retryItem->attempt());
    }

    public function testItMustReturnNullThereIsNotAttemptsRetry()
    {
        $this->messageMock->expects($this->exactly(3))
            ->method('getHeader')
            ->withConsecutive(['retry_name'], ['retry_name'], ['retry_attempt'])
            ->willReturnOnConsecutiveCalls('retry-2','retry-2', 3);

        $retryConfig = new RetryConfig(
            ...[$this->createRetryItem('retry-1'), $this->createRetryItem('retry-2')]
        );
        self::assertNull($retryConfig->nextRetry($this->messageMock));
    }

    private function createRetryItem(string $name): RetryItem
    {
        return new RetryItem(
            commandRetry: 'Test/Class',
            name: $name,
            queue: 'test-queue',
            interval: 60,
            attempts: 3,
        );
    }
}
