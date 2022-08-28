<?php

namespace Unit\Retry;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\Retry\RetryAttempt;
use Wesley\Enqueue\Retry\RetryItem;

class RetryAttemptTest extends TestCase
{
    public function testItMustReturnArrayWithHeaderForMessage()
    {
        $retryItem = $this->createRetryItem();
        $retryAttempt = new RetryAttempt($retryItem);

        $attemptDate = new DateTimeImmutable();
        $attemptDate = $attemptDate->add(new DateInterval('PT' . 60 . 'S'));

        $expected = [
            'retry_command' => 'Test/Class',
            'retry_name' => 'test',
            'retry_attempt_date' => $attemptDate->format(DateTimeInterface::ATOM),
            'retry_attempt' => 1
        ];

        self::assertEquals($expected, $retryAttempt->toHeader());
    }

    public function testItMustReturnQueue()
    {
        $retryItem = $this->createRetryItem();
        $retryAttempt = new RetryAttempt($retryItem);

        self::assertEquals('test-queue', $retryAttempt->queue());
    }

    private function createRetryItem(): RetryItem
    {
        return new RetryItem(
            commandRetry: 'Test/Class',
            name: 'test',
            queue: 'test-queue',
            interval: 60,
            attempts: 1,
        );
    }
}
