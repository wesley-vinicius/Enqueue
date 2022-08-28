<?php

namespace Wesley\Enqueue\Retry;

use InvalidArgumentException;

final class RetryItem
{
    private int $currentAttempt = 0;

    public function __construct(
        private string $commandRetry,
        private string $name,
        private string $queue,
        private int    $interval,
        private int    $attempts
    ) {
        $this->validate();
    }

    private function validate()
    {
        if ($this->commandRetry === '') {
            throw new InvalidArgumentException("commandRetry nao pode ser vazio");
        }

        if ($this->name === '') {
            throw new InvalidArgumentException("name nao pode ser vazio");
        }

        if ($this->queue === '') {
            throw new InvalidArgumentException("queue nao pode ser vazio");
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function queue(): string
    {
        return $this->queue;
    }

    public function interval(): int
    {
        return $this->interval;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function addCurrentAttempt(int $attempt): RetryItem
    {
        $this->currentAttempt = $attempt;
        return $this;
    }

    public function currentAttempt(): int
    {
        return $this->currentAttempt;
    }

    public function commandRetry(): string
    {
        return $this->commandRetry;
    }

    public function nextAttempt(): ?RetryAttempt
    {
        if ($this->currentAttempt >= $this->attempts) {
            return null;
        }

        return new RetryAttempt($this);
    }
}
