<?php

declare(strict_types=1);

namespace Aljerom\CqrsEvents\CQRS\Messenger;

use Aljerom\CqrsEvents\CQRS\Command;
use Aljerom\CqrsEvents\CQRS\CommandBus;
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
