<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\CQRS\Messenger;

use Aljerom\SymfonyBoilerplate\CQRS\Query;
use Aljerom\SymfonyBoilerplate\CQRS\QueryBus;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerQueryBus implements QueryBus
{
    use HandleTrait;
    use DispatchTrait;

    public function __construct(private MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    public function query(Query $query): mixed
    {
        return $this->dispatchWrap($query);
    }
}
