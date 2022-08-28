<?php

namespace Unit\Command;

use Exception;
use Interop\Queue\Message;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wesley\Enqueue\Consumer;
use Wesley\Enqueue\Factories\ConsumerKafkaFactory;
use Symfony\Component\Console\Exception\InvalidArgumentException as SymfonyInvalidArgumentException;
use Wesley\Enqueue\Command\BaseConsumeCommand;
use Wesley\Enqueue\Factories\RetryFactory;
use Wesley\Enqueue\Retry\Retry;

class BaseConsumeCommandTest extends TestCase
{
    protected LoggerInterface $loggerMock;
    private ConsumerKafkaFactory $factoryMock;
    private Consumer $consumerMock;
    private array $config = [];
    private RetryFactory $retryFactoryMock;
    private Retry $retryMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->factoryMock = $this->createMock(ConsumerKafkaFactory::class);
        $this->consumerMock = $this->createMock(Consumer::class);
        $this->retryFactoryMock = $this->createMock(RetryFactory::class);
        $this->retryMock = $this->createMock(Retry::class);
        $this->config = [];
    }

    public function testItMustThrowExceptionWhenNoInformedQueueName()
    {
        self::expectException(InvalidArgumentException::class);

        $this->getBaseWorkerWorkerConsumer('', '');
    }

    public function testItMustThrowExceptionWhenNoInformedCommandName()
    {
       self::expectException(SymfonyInvalidArgumentException::class);

        $this->getBaseWorkerWorkerConsumer('test', '', '');
    }

    public function testItMustReturnQueueInformedInArrayConfigAndNoClassProperty()
    {
        $this->config['queue'] = 'test-config-queue';
        $class = $this->getBaseWorkerWorkerConsumer('', '');

        self::assertEquals('test-config-queue', $class->queue());
    }

    public function testItMustReturnQueueInformedInClassProperty()
    {
        $class = $this->getBaseWorkerWorkerConsumer('test-queue', '');
        self::assertEquals('test-queue', $class->queue());
    }

    public function testItMustAddInConfigDeadQueueInformedInClassAttribute()
    {
        $classRe = new \ReflectionClass(BaseConsumeCommand::class);
        $myProtectedProperty = $classRe->getProperty('config');
        $myProtectedProperty->setAccessible(true);
        $class = $this->getBaseWorkerWorkerConsumer('test', 'test-dead-queue');
        $config = $myProtectedProperty->getValue($class);
        self::assertEquals('test-dead-queue', $config['dead_queue']);
    }

    public function testItMustProcessMessage()
    {
        $this->consumerMock->expects($this->once())
            ->method('consume');

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($this->config)
            ->willReturn($this->consumerMock);

        $class = $this->getBaseWorkerWorkerConsumer('test', '');

        $mockInputInterface = $this->createMock(InputInterface::class);
        $mockOutputInterface = $this->createMock(OutputInterface::class);
        $result = $class->execute($mockInputInterface, $mockOutputInterface);

        self::assertEquals(0, $result);
    }

    public function testItMustLoggerAndRethrow()
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage("test error");

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Erro ao processar mensagem test error');

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($this->config)
            ->willReturn($this->consumerMock);

        $class = $this->getBaseWorkerWorkerConsumer('test', '');

        $mockMessage = $this->createMock(Message::class);
        $mockMessage->expects($this->once())
            ->method('getBody')
            ->willThrowException(new Exception('test error'));

        $class->process($mockMessage);
    }

    public function testItMustCallRetryWhenAreAttemptsConfiguration()
    {
        $mockMessage = $this->createMock(Message::class);

        $this->config['retry'] = [
            [
                'name' => 'retry-1',
                'queue' => 'retry-1',
                'interval' => 60,
                'attempts' => 3
            ]
        ];

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($this->config)
            ->willReturn($this->consumerMock);

        $this->retryMock->expects($this->once())
            ->method('push')
            ->with($mockMessage)
            ->willReturn(true);

        $this->retryFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->retryMock);

        $class = $this->getBaseWorkerWorkerConsumer('test', '');

        $mockMessage->expects($this->once())
            ->method('getBody')
            ->willThrowException(new Exception('test error'));

        $class->process($mockMessage);
    }

    public function testItMustReThrowExceptionWhenWithoutAttemptRetry()
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage('test error');

        $mockMessage = $this->createMock(Message::class);

        $this->config['retry'] = [
            [
                'name' => 'retry-1',
                'queue' => 'retry-1',
                'interval' => 60,
                'attempts' => 3
            ]
        ];

        $this->factoryMock->expects($this->once())
            ->method('create')
            ->with($this->config)
            ->willReturn($this->consumerMock);

        $this->retryMock->expects($this->once())
            ->method('push')
            ->with($mockMessage)
            ->willReturn(false);

        $this->retryFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->retryMock);

        $class = $this->getBaseWorkerWorkerConsumer('test', '');

        $mockMessage->expects($this->once())
            ->method('getBody')
            ->willThrowException(new Exception('test error'));

        $class->process($mockMessage);
    }

    /**
     * @param string $name
     * @param string $queue
     * @param string $deadQueue
     * @return BaseConsumeCommand
     */
    private function getBaseWorkerWorkerConsumer(string $queue, string $deadQueue, string $name = 'test:test'): BaseConsumeCommand
    {
        return new class($this->factoryMock, $this->loggerMock, $this->retryFactoryMock, $this->config, $name, $queue, $deadQueue) extends BaseConsumeCommand {
            public function __construct(
                ConsumerKafkaFactory $factory,
                LoggerInterface      $logger,
                RetryFactory         $retryFactory,
                array                $config,
                string               $name,
                string               $queue = '',
                string               $deadQueue = '',
            ) {
                $this->queue = $queue;
                $this->deadQueue = $deadQueue;
                $this->name = $name;
                parent::__construct($factory, $logger, $retryFactory, $config);
            }

            protected function consumer(Message $message, ?array $data = null)
            {
                $message->getBody();
            }
        };
    }
}
