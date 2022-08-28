<?php

namespace Wesley\Enqueue\Contracts;

interface IProducer
{
    public function producer(string $topic, array $message,  array $header = [], array $properties  = []);
}
