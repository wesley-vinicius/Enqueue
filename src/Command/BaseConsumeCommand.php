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

abstract class BaseConsumeCommand extends Command implements IConsumeCommand
{
    protected IConsumer $consumer;

    protected string $name = '';
    protected string $queue = '';
    protected string $deadQueue = '';

    public function __construct(
        protected LoggerInterface $logger,
        ConsumerKafkaFactory $factory,
        private array $config = [],
    ) {
        parent::__construct($this->name);
        $this->setup();
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

            throw $throwable;
        }
    }

    protected abstract function consumer(Message $message);

    private function setup(): void
    {
        if ($this->queue() === '') {
           throw new InvalidArgumentException(
               'Nome da fila deve ser informado',
           );
        }

        if ($this->deadQueue != '') {
            $this->config['dead_queue'] = $this->deadQueue;
        }
    }

    public function queue(): string
    {
        return $this->config['queue'] ?? $this->queue;
    }
}
