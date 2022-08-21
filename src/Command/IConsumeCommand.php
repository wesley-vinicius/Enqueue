<?php

namespace Wesley\Enqueue\Command;

use Interop\Queue\Message;

interface IConsumeCommand
{
    public function process(Message $message);
}
