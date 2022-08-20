<?php

namespace Wesley\Enqueue\Contracts;

interface IConsumer
{
    public function consume(string $queue, callable $callable, mixed $shutdown);
}
