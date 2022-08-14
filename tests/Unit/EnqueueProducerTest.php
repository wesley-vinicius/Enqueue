<?php

namespace Unit;

use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\EnqueueProducer;

class EnqueueProducerTest extends TestCase
{
    private ConnectionFactory $connectionFactoryMock;
    private Context $contextMock;
    private Topic $topicMock;
    private Producer $producerMock;
    private Message $messageMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->connectionFactoryMock = $this->createMock(ConnectionFactory::class);
        $this->contextMock = $this->createMock(Context::class);
        $this->topicMock = $this->createMock(Topic::class);
        $this->producerMock = $this->createMock(Producer::class);
        $this->messageMock = $this->createMock(Message::class);
    }

    public function testItMustPushMessageToKafka()
    {
        $topic = 'topic_test';
        $message = ['test-message' => 'test'];
        $properties = ['test-properties' => 'test'];
        $header = ['test-header' => 'test'];

        $this->contextMock->expects($this->once())
            ->method('createTopic')
            ->with($topic)
            ->willReturn($this->topicMock);

        $this->contextMock->expects($this->once())
            ->method('createProducer')
            ->willReturn($this->producerMock);

        $this->contextMock->expects($this->once())
            ->method('createMessage')
            ->with(json_encode($message), $properties, $header)
            ->willReturn($this->messageMock);

        $this->connectionFactoryMock->expects($this->once())
            ->method('createContext')
            ->willReturn($this->contextMock);

        $this->producerMock->expects($this->once())
            ->method('send')
            ->with($this->topicMock, $this->messageMock);

        $RdKafkaEnqueue = new EnqueueProducer($this->connectionFactoryMock);
        $RdKafkaEnqueue->producer($topic, $message, $header, $properties);
    }
}
