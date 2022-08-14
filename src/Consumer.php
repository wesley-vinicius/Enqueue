<?php

namespace Wesley\Enqueue;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Throwable;
use Wesley\Enqueue\Contracts\IConsumer;

class Consumer implements IConsumer
{
    private Context $context;

    public function __construct(
        ConnectionFactory $connectionFactory,
        private array $config = [],
    ) {
        $this->context = $connectionFactory->createContext();
    }

    public function consumer(string $queue, callable $callable, mixed $shutdown = true)
    {
        $queue = $this->context->createQueue($queue);
        $consumer = $this->context->createConsumer($queue);

        do {
            $message = $consumer->receive();
            if (is_null($message)) {
                continue;
            }

            try {
                $callable($message);
            } catch (Throwable $throwable) {
                if ($deadQueue = $this->config['dead_queue'] ?? null) {
                    $this->pushDeadQueue($deadQueue, $message, $throwable);
                }
            }

            $consumer->acknowledge($message);
        } while ($shutdown);
    }

    private function pushDeadQueue(string $topic, Message $message, Throwable $throwable)
    {
        $producer = $this->context->createProducer();
        $topic = $this->context->createTopic($topic);
        $message->setHeader('exception_class', $throwable::class);
        $message->setHeader('exception_message', $throwable->getMessage());

        $producer->send($topic, $message);
    }
}
