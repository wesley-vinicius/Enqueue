<?php

namespace Unit\Retry;

use Interop\Queue\Message;
use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\Contracts\IProducer;
use Wesley\Enqueue\Retry\Retry;
use Wesley\Enqueue\Retry\RetryAttempt;
use Wesley\Enqueue\Retry\RetryConfig;
use Wesley\Enqueue\Retry\RetryItem;

class RetryTest extends TestCase
{
    private IProducer $producerMock;
    private Message $messageMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->producerMock = $this->createMock(IProducer::class);
        $this->messageMock = $this->createMock(Message::class);
    }

    public function testItMustPushRetry()
    {
        $retry = $this->createRetryItem();
        $retryAttempt = new RetryAttempt($retry);
        $this->messageMock->expects($this->once())
            ->method('getBody')
            ->willReturn("{\"test\":\"test\"}");

        $this->producerMock->expects($this->once())
            ->method('producer')
            ->willReturn('test-queue', ['test' => 'test'], $retryAttempt->toHeader());

        $retry = new Retry(
            $this->producerMock,
            new RetryConfig($retry),
        );

        self::assertTrue($retry->push($this->messageMock));
    }

    public function testItMustDoNothingWhenThereIsNotNextRetry()
    {
        $this->messageMock->expects($this->exactly(3))
            ->method('getHeader')
            ->withConsecutive(['retry_name'], ['retry_name'], ['retry_attempt'])
            ->willReturnOnConsecutiveCalls('retry-1','retry-1', 1);

        $retry = new Retry(
            $this->producerMock,
            new RetryConfig($this->createRetryItem()),
        );

        self::assertFalse($retry->push($this->messageMock));
    }

    private function createRetryItem(): RetryItem
    {
        return new RetryItem(
            commandRetry: 'Test/Class',
            name: 'retry-1',
            queue: 'test-queue',
            interval: 60,
            attempts: 1,
        );
    }
}
