<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\CQRS\Messenger;

use Aljerom\SymfonyBoilerplate\CQRS\Command;
use Aljerom\SymfonyBoilerplate\CQRS\CommandBus;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerCommandBus implements CommandBus
{
    use HandleTrait;
    use DispatchTrait;

    public function __construct(private MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    public function dispatch(Command $command): mixed
    {
        return $this->dispatchWrap($command);
    }
}
