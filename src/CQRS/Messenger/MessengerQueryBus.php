<?php

declare(strict_types=1);

namespace Aljerom\CqrsEvents\CQRS\Messenger;

use Aljerom\CqrsEvents\CQRS\Query;
use Aljerom\CqrsEvents\CQRS\QueryBus;
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

    public function handle(Query $query): mixed
    {
        return $this->dispatchWrap($query);
    }
}
