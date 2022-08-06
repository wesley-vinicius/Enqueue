<?php

namespace Wesley\Enqueue\Brokers\Contracts;

interface IEnqueue
{
    public function producer(string $topic, array $message);
}