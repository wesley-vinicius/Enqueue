<?php

namespace Unit\Command;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Interop\Queue\Message;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Wesley\Enqueue\Command\IConsumeCommand;
use Wesley\Enqueue\Command\RetryConsumeCommand;
use Wesley\Enqueue\Factories\ConsumerKafkaFactory;

class RetryConsumeCommandTest extends TestCase
{
    protected ContainerInterface $containerMock;
    protected LoggerInterface $loggerMock;
    private ConsumerKafkaFactory $factoryMock;
    private array $config = [];
    private Message $messageMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->factoryMock = $this->createMock(ConsumerKafkaFactory::class);
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->messageMock = $this->createMock(Message::class);
        $this->config = [];
    }

    public function testItMustProcessMessage()
    {
        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Esperando 1 segundos para executar');

        $this->messageMock->expects($this->exactly(2))
            ->method('getHeader')
            ->withConsecutive(['retry_command'], ['retry_attempt_date'])
            ->willReturnOnConsecutiveCalls('class/retry', $this->attemptDate());

        $classMock = $this->createMock(IConsumeCommand::class);
        $classMock->expects($this->once())
            ->method('process')
            ->with($this->messageMock);

        $this->containerMock->expects($this->once())
            ->method('get')
            ->with('class/retry')
            ->willReturn($classMock);

        $command = $this->createRetryConsumeCommand();

        $dateTimeStart = new DateTimeImmutable();
        $command->process($this->messageMock);
        $dateTimeFinish = new DateTimeImmutable();

        self::assertEquals(2, $dateTimeStart->diff($dateTimeFinish)->s);
    }

    /**
     * @dataProvider getDate
     */
    public function testItMustProcessMessageWhenDateTimeExecIsGreaterDateTimeRetryExec(string $dateTime)
    {
        $this->messageMock->expects($this->exactly(2))
            ->method('getHeader')
            ->withConsecutive(['retry_command'], ['retry_attempt_date'])
            ->willReturnOnConsecutiveCalls('class/retry', $dateTime);

        $classMock = $this->createMock(IConsumeCommand::class);
        $classMock->expects($this->once())
            ->method('process')
            ->with($this->messageMock);

        $this->containerMock->expects($this->once())
            ->method('get')
            ->with('class/retry')
            ->willReturn($classMock);

        $command = $this->createRetryConsumeCommand();

        $dateTimeStart = new DateTimeImmutable();
        $command->process($this->messageMock);
        $dateTimeFinish = new DateTimeImmutable();

        self::assertEquals(0, $dateTimeStart->diff($dateTimeFinish)->s);
    }

    public function testItMustLoggerWhenClassInvalid()
    {
        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Error no retry: Class de retry nao e da instancia IConsumeCommand');

        $this->messageMock->expects($this->once())
            ->method('getHeader')
            ->with('retry_command')
            ->willReturn('class/retry');

        $this->containerMock->expects($this->once())
            ->method('get')
            ->with('class/retry')
            ->willReturn(new stdClass());

        $command = $this->createRetryConsumeCommand();

        $command->process($this->messageMock);
    }

    private function createRetryConsumeCommand(): RetryConsumeCommand
    {
        return new RetryConsumeCommand(
            $this->containerMock,
            $this->factoryMock,
            $this->loggerMock,
            $this->config
        );
    }

    private function attemptDate($secund = 2): string
    {
        $attemptDate = new DateTimeImmutable();
        return $attemptDate->modify("+$secund seconds")->format(DATE_ATOM);
    }

    public function getDate(): array
    {
        return [
            [(new DateTime())->format(DATE_ATOM)],
            [(new DateTime())->modify('-1 seconds')->format(DATE_ATOM)],
        ];
    }
}
