<?php

namespace Wesley\Enqueue;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;

class EnqueueProducer implements IEnqueueProducer
{
    private Context $context;

    public function __construct(
        ConnectionFactory $connectionFactory
    ) {
        $this->context = $connectionFactory->createContext();
    }

    public function producer(string $topic, array $message, array $header = [], array $properties  = [])
    {
        $topic = $this->context->createTopic($topic);
        $producer = $this->context->createProducer();
        $message = $this->context->createMessage(json_encode($message), $properties, $header);

        $producer->send($topic, $message);
    }
}
