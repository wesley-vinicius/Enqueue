<?php

namespace Wesley\Enqueue\Retry;

use Interop\Queue\Message;
use Wesley\Enqueue\Contracts\IProducer;

class Retry
{
    public function __construct(
        private IProducer $producer,
        private RetryConfig $retryConfig
    ) {
    }

    public function push(Message $message): bool
    {
        $nextRetry = $this->retryConfig->nextRetry($message);

        if (is_null($nextRetry)) {
            return false;
        }

        $this->producer->producer(
            $nextRetry->queue(),
            json_decode($message->getBody(), true),
            $nextRetry->toHeader()
        );

        return true;
    }

    public function config(): RetryConfig
    {
        return $this->retryConfig;
    }
}
