<?php

namespace Wesley\Enqueue\Contracts;

interface IConsumer
{
    public function consumer(string $queue, callable $callable, mixed $shutdown);
}
