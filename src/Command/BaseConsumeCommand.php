<?php

namespace Wesley\Enqueue\Command;

use Interop\Queue\Message;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Wesley\Enqueue\Contracts\IConsumer;
use Wesley\Enqueue\Factories\ConsumerKafkaFactory;
use Wesley\Enqueue\Factories\RetryFactory;
use Wesley\Enqueue\Retry\Retry;

abstract class BaseConsumeCommand extends Command implements IConsumeCommand
{
    protected IConsumer $consumer;
    protected ?Retry $pushRetry = null;

    protected string $name = '';
    protected string $queue = '';
    protected string $deadQueue = '';
    private bool $retry = false;

    public function __construct(
        ConsumerKafkaFactory $factory,
        protected LoggerInterface $logger,
        protected ?RetryFactory $retryFactory = null,
        protected array $config = [],
    ) {
        parent::__construct($this->name);
        $this->setup($factory);
    }

    protected abstract function consumer(Message $message);

    private function setup(ConsumerKafkaFactory $factory): void
    {
        if ($this->queue() === '') {
            throw new InvalidArgumentException(
                'Nome da fila deve ser informado',
            );
        }

        if ($this->deadQueue != '') {
            $this->config['dead_queue'] = $this->deadQueue;
        }

        if (isset($this->config['retry']) && !empty($this->config['retry'])) {
            $this->retry = true;
            $this->pushRetry = $this->retryFactory->create($this::class, $this->config);
        }

        $this->consumer = $factory->create($this->config);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->consumer->consume($this->queue(), fn(Message $message) => $this->process($message));

        return Command::SUCCESS;
    }

    public function process(Message $message)
    {
        try {
            $this->consumer($message, []);
        } catch (Throwable $throwable) {
            $this->logger->error(
                'Erro ao processar mensagem ' . $throwable->getMessage()
            );

            if ($this->retry) {
                $this->retryPush($message, $throwable);
                return;
            }

            throw $throwable;
        }
    }

    private function retryPush(Message $message, Throwable $throwable): void
    {
        if (!$this->pushRetry->push($message)) {
            throw $throwable;
        }
    }

    public function queue(): string
    {
        return $this->config['queue'] ?? $this->queue;
    }
}
