<?php

namespace Brokers;

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\Brokers\RdKafkaEnqueue;

class RdKafkaEnqueueTest extends TestCase
{
    private Context $contextMock;
    private Topic $topicMock;
    private Producer $producerMock;
    private Message $messageMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->contextMock = $this->createMock(Context::class);
        $this->topicMock = $this->createMock(Topic::class);
        $this->producerMock = $this->createMock(Producer::class);
        $this->messageMock = $this->createMock(Message::class);
    }

    public function testItMustPushMessageToKafka()
    {
        $topic = 'topic_test';
        $message = ['test'];

        $this->contextMock->expects($this->once())
            ->method('createTopic')
            ->with($topic)
            ->willReturn($this->topicMock);

        $this->contextMock->expects($this->once())
            ->method('createProducer')
            ->willReturn($this->producerMock);

        $this->contextMock->expects($this->once())
            ->method('createMessage')
            ->with(json_encode($message))
            ->willReturn($this->messageMock);

        $this->producerMock->expects($this->once())
            ->method('send')
            ->with($this->topicMock, $this->messageMock);

        $RdKafkaEnqueue = new RdKafkaEnqueue($this->contextMock);
        $RdKafkaEnqueue->producer($topic, $message);
    }
}
