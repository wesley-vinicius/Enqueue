<?php

namespace Wesley\Enqueue;

interface IEnqueueProducer
{
    public function producer(string $topic, array $message);
}
