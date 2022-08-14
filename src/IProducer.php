<?php

namespace Wesley\Enqueue;

interface IProducer
{
    public function producer(string $topic, array $message);
}
