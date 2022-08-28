<?php

namespace Wesley\Enqueue\Command;

use DateTimeImmutable;
use Interop\Queue\Message;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Wesley\Enqueue\Factories\ConsumerKafkaFactory;

class RetryConsumeCommand extends BaseConsumeCommand
{
    protected string $name = 'test-retry';
    protected string $queue = 'retry-1';

    public function __construct(
        private ContainerInterface $container,
        ConsumerKafkaFactory $factory,
        LoggerInterface $logger,
        array $config
    ) {
        parent::__construct($factory, $logger, null, $config);
    }

    protected function consumer(Message $message)
    {
        try {
            $retryCommandClass = $message->getHeader('retry_command');

            $retryCommand = $this->getRetryCommand($retryCommandClass);

            $now = new DateTimeImmutable();
            $timeExecRetry = new DateTimeImmutable($message->getHeader('retry_attempt_date'));

            if ($now < $timeExecRetry) {
                $this->logger->info("Esperando {$now->diff($timeExecRetry)->s} segundos para executar");
                sleep(2);
            }

            $retryCommand->process($message);
        } catch (Throwable $throwable) {
            $this->logger->error('Error no retry: ' . $throwable->getMessage(), $throwable->getTrace());
        }
    }

    private function getRetryCommand(string $class): IConsumeCommand
    {
        $retryCommand = $this->container->get($class);

        if (!$retryCommand instanceof IConsumeCommand) {
            throw new InvalidArgumentException(
                "Class de retry nao e da instancia IConsumeCommand"
            );
        }

        return $retryCommand;
    }
}
