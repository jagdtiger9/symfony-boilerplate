<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Aljerom\CqrsEvents\CQRS\CommandBus;
use Aljerom\CqrsEvents\CQRS\Messenger\MessengerCommandBus;
use Aljerom\CqrsEvents\CQRS\Messenger\MessengerQueryBus;
use Aljerom\CqrsEvents\CQRS\QueryBus;
use Aljerom\CqrsEvents\DomainEvents\Doctrine\PersistDomainEventListener;
use Doctrine\ORM\Events;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(MessengerCommandBus::class)->autowire();
    $services->alias(CommandBus::class, MessengerCommandBus::class);

    $services->set(MessengerQueryBus::class)->autowire();
    $services->alias(QueryBus::class, MessengerQueryBus::class);

    $services->set(PersistDomainEventListener::class)
        ->autowire()
        ->tag('doctrine.event_listener', [
            'event' => Events::onFlush,
            'priority' => 500,
        ]);
};
