<?php

namespace Unit;

use Exception;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Consumer as ConsumerInterop;
use Interop\Queue\Producer as ProducerInterop;
use Interop\Queue\Queue;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;
use Wesley\Enqueue\Consumer;

class ConsumerTest extends TestCase
{
    private ConnectionFactory $connectionFactoryMock;
    private Context $contextMock;
    private Queue $queueMock;
    private ProducerInterop $producerMock;
    private ConsumerInterop $consumerMock;
    private Message $messageMock;
    private Topic $topicMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->connectionFactoryMock = $this->createMock(ConnectionFactory::class);
        $this->contextMock = $this->createMock(Context::class);
        $this->queueMock = $this->createMock(Queue::class);
        $this->topicMock = $this->createMock(Topic::class);
        $this->consumerMock = $this->createMock(ConsumerInterop::class);
        $this->producerMock = $this->createMock(ProducerInterop::class);
        $this->messageMock = $this->createMock(Message::class);
    }

    public function testItMustCallCallback()
    {
        $queue = 'test';

        $this->contextMock->expects($this->once())
            ->method('createQueue')
            ->with($queue)
            ->willReturn($this->queueMock);

        $this->consumerMock->expects($this->once())
            ->method('receive')
            ->willReturn($this->messageMock);

        $this->consumerMock->expects($this->once())
            ->method('acknowledge');

        $this->contextMock->expects($this->once())
            ->method('createConsumer')
            ->willReturn($this->consumerMock);

        $this->connectionFactoryMock->expects($this->once())
            ->method('createContext')
            ->willReturn($this->contextMock);

        $consumer = new Consumer($this->connectionFactoryMock);

        $callback = fn (Message $message) => self::assertEquals($message, $this->messageMock);

        $consumer->consumer('test', $callback, false);
    }

    public function testItMustPostMessageInDLQWhenProcessInError()
    {
        $queue = 'test';
        $config = ['dead_queue' => 'test-DLQ'];

        $this->contextMock->expects($this->once())
            ->method('createQueue')
            ->with($queue)
            ->willReturn($this->queueMock);

        $this->contextMock->expects($this->once())
            ->method('createTopic')
            ->with($config['dead_queue'])
            ->willReturn($this->topicMock);

        $this->producerMock->expects($this->once())
            ->method('send')
            ->with($this->topicMock, $this->messageMock);

        $this->messageMock->expects($this->exactly(2))
            ->method('setHeader')
            ->withConsecutive(
                ['exception_class', Exception::class],
                ['exception_message', 'error']
            );

        $this->contextMock->expects($this->once())
            ->method('createProducer')
            ->willReturn($this->producerMock);

        $this->consumerMock->expects($this->once())
            ->method('receive')
            ->willReturn($this->messageMock);

        $this->consumerMock->expects($this->once())
            ->method('acknowledge');

        $this->contextMock->expects($this->once())
            ->method('createConsumer')
            ->willReturn($this->consumerMock);

        $this->connectionFactoryMock->expects($this->once())
            ->method('createContext')
            ->willReturn($this->contextMock);

        $consumer = new Consumer($this->connectionFactoryMock, $config);

        $callback = fn (Message $message) => throw new Exception('error');

        $consumer->consumer('test', $callback, false);
    }

    public function testItMustProcessInErrorNoConfigDLQ()
    {
        $queue = 'test';

        $this->contextMock->expects($this->once())
            ->method('createQueue')
            ->with($queue)
            ->willReturn($this->queueMock);

        $this->consumerMock->expects($this->once())
            ->method('receive')
            ->willReturn($this->messageMock);

        $this->consumerMock->expects($this->once())
            ->method('acknowledge');

        $this->contextMock->expects($this->once())
            ->method('createConsumer')
            ->willReturn($this->consumerMock);

        $this->connectionFactoryMock->expects($this->once())
            ->method('createContext')
            ->willReturn($this->contextMock);

        $consumer = new Consumer($this->connectionFactoryMock);

        $callback = fn (Message $message) => throw new Exception('error');

        $consumer->consumer('test', $callback, false);
    }
}
