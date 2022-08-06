<?php

namespace Wesley\Enqueue\Brokers;

use Interop\Queue\Context;
use Wesley\Enqueue\Brokers\Contracts\IEnqueue;

class RdKafkaEnqueue implements IEnqueue
{
    public function __construct(
        private Context $context
    ) {
    }

    public function producer(string $topic, array $message)
    {
        $topic = $this->context->createTopic($topic);
        $producer = $this->context->createProducer();

        $producer->send(
            $topic,
            $this->context->createMessage(json_encode($message))
        );
    }
}
