<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Aljerom\SymfonyBoilerplate\CQRS\CommandBus;
use Aljerom\SymfonyBoilerplate\CQRS\Messenger\MessengerCommandBus;
use Aljerom\SymfonyBoilerplate\CQRS\Messenger\MessengerQueryBus;
use Aljerom\SymfonyBoilerplate\CQRS\QueryBus;
use Aljerom\SymfonyBoilerplate\DomainEvents\Doctrine\PersistDomainEventListener;
use Aljerom\SymfonyBoilerplate\EventSubscriber\AddErrorDetailsStampSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\ConsoleDumperSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\DomainEventPublishSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\KernelRequestSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\ResponseSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\ValidationExceptionListener;
use Aljerom\SymfonyBoilerplate\Request\MessageValueResolver;
use Doctrine\ORM\Events;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->set(MessengerCommandBus::class);
    $services->alias(CommandBus::class, MessengerCommandBus::class);

    $services->set(MessengerQueryBus::class);
    $services->alias(QueryBus::class, MessengerQueryBus::class);

    $services->set(PersistDomainEventListener::class)
        ->tag('doctrine.event_listener', [
            'event' => Events::onFlush,
            'priority' => 500,
        ]);

    $services->set(DomainEventPublishSubscriber::class);
    $services->set(ValidationExceptionListener::class);
    $services->set(KernelRequestSubscriber::class)
        ->arg('$defaultTimeZone', '%symfony_boilerplate.timezone%')
        ->arg('$defaultLocale', '%symfony_boilerplate.locale%');

    $services->set(ResponseSubscriber::class);

    $services->set(ConsoleDumperSubscriber::class)
        ->arg('$defaultTimeZone', '%symfony_boilerplate.timezone%');
    $services->set(MessageValueResolver::class);

    // Replaces Symfony's default listener to avoid including FlattenException in the stamp
    $services->set('messenger.failure.add_error_details_stamp_listener')
        ->class(AddErrorDetailsStampSubscriber::class)
        ->tag('kernel.event_subscriber');
};
