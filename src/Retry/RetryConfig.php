<?php

namespace Wesley\Enqueue\Retry;

use Interop\Queue\Message;
use InvalidArgumentException;

class RetryConfig
{
    private array $retryItems;

    public function __construct(RetryItem ...$retryItems)
    {
        $this->retryItems = $retryItems;
    }

    public static function fromArray(string $commandRetry, array $data): RetryConfig
    {
        return new RetryConfig(
            ...array_map(function (array $item) use ($commandRetry){
                return new RetryItem(
                    commandRetry: $commandRetry,
                    name: $item['name'],
                    queue: $item['queue'],
                    interval: $item['interval'],
                    attempts: $item['attempts']
                );
            }, $data)
        );
    }

    public function nextRetry(Message $message): ?RetryAttempt
    {
       if (!$this->isProcessToRetry($message)) {
           return current($this->retryItems)->nextAttempt();
       }

       $currentRetry = $this->findItemByName($message->getHeader('retry_name'));
       if (!$currentRetry) {
           throw new InvalidArgumentException(
               "retry {$message->getHeader('retry_name')} nao encontado"
           );
       }

       $nextRetryAttempt = $currentRetry
           ->addCurrentAttempt($message->getHeader('retry_attempt'))
           ->nextAttempt();

       if (is_null($nextRetryAttempt)) {
           $nextRetryAttempt = $this->next($currentRetry)?->nextAttempt();
       }

       return $nextRetryAttempt;
    }

    private function findItemByName(string $retryName): ?RetryItem
    {
        $retryFilter = array_filter($this->retryItems, function (RetryItem $retryItem) use ($retryName) {
            return $retryItem->name() === $retryName;
        });

        if (!$retryFilter) {
            return null;
        }

        return current($retryFilter);
    }

    private function isProcessToRetry(Message $message): bool
    {
        return !empty($message->getHeader('retry_name'));
    }

    private function next(RetryItem $currentRetry): ?RetryItem
    {
        $next = next($this->retryItems);

        return !$next ? null : $next;
    }

    public function items(): array
    {
        return $this->retryItems;
    }
}
