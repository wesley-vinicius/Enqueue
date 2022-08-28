<?php

namespace Wesley\Enqueue\Retry;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;

class RetryAttempt
{
    private string $commandRetry;
    private string $name;
    private string $queue;
    private DateTimeInterface $attemptDate;
    private int $attempt;

    public function __construct(RetryItem $retryItem)
    {
        $this->commandRetry = $retryItem->commandRetry();
        $this->name = $retryItem->name();
        $this->queue = $retryItem->queue();
        $this->attemptDate = $this->createAttemptDate($retryItem->interval());
        $this->attempt = $retryItem->currentAttempt() + 1;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function attempt(): int
    {
        return $this->attempt;
    }

    public function queue(): string
    {
        return $this->queue;
    }

    private function createAttemptDate(int $interval): DateTimeInterface
    {
        $attemptDate = new DateTimeImmutable();
        return $attemptDate->add(new DateInterval('PT' . $interval . 'S'));
    }

    public function toHeader(): array
    {
        return [
            'retry_command' => $this->commandRetry,
            'retry_name' => $this->name,
            'retry_attempt_date' => $this->attemptDate->format(DateTimeInterface::ATOM),
            'retry_attempt' => $this->attempt
        ];
    }
}
