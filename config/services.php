<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Aljerom\SymfonyBoilerplate\CQRS\CommandBus;
use Aljerom\SymfonyBoilerplate\CQRS\Messenger\MessengerCommandBus;
use Aljerom\SymfonyBoilerplate\CQRS\Messenger\MessengerQueryBus;
use Aljerom\SymfonyBoilerplate\CQRS\QueryBus;
use Aljerom\SymfonyBoilerplate\DomainEvents\Doctrine\PersistDomainEventListener;
use Aljerom\SymfonyBoilerplate\DomainEvents\NullEventRepository;
use Aljerom\SymfonyBoilerplate\EventSubscriber\AddErrorDetailsStampSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\ConsoleDumperSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\DomainEventPublishSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\KernelRequestSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\ResponseSubscriber;
use Aljerom\SymfonyBoilerplate\EventSubscriber\ValidationExceptionListener;
use Aljerom\SymfonyBoilerplate\Request\MessageValueResolver;
use Doctrine\ORM\Events;

return App::config([
    'services' => [
        '_defaults' => [
            'autowire' => true,
            'autoconfigure' => true,
        ],

        MessengerCommandBus::class => [],
        CommandBus::class => '@'.MessengerCommandBus::class,

        MessengerQueryBus::class => [],
        QueryBus::class => '@'.MessengerQueryBus::class,

        // the default no-op event repository; the EventRepositoryInterface alias
        // is set in SymfonyBoilerplateBundle::loadExtension() only when the
        // consuming project does not bind the interface itself
        NullEventRepository::class => [],

        // listeners are applied by default to all Doctrine connections
        PersistDomainEventListener::class => [
            'tags' => [
                [
                    'doctrine.event_listener' => [
                        'event' => Events::onFlush,
                        'priority' => 500,
                        // you can also restrict listeners to a specific Doctrine connection
                        //'connection' => 'default',
                    ],
                ],
            ],
        ],

        DomainEventPublishSubscriber::class => [],
        ValidationExceptionListener::class => [],

        KernelRequestSubscriber::class => [
            'arguments' => [
                '$defaultTimeZone' => '%symfony_boilerplate.timezone%',
                '$defaultLocale' => '%symfony_boilerplate.locale%',
            ],
        ],

        ResponseSubscriber::class => [],

        ConsoleDumperSubscriber::class => [
            'arguments' => [
                '$defaultTimeZone' => '%symfony_boilerplate.timezone%',
            ],
        ],

        MessageValueResolver::class => [],

        // Replaces Symfony's default listener to avoid including FlattenException in the stamp
        'messenger.failure.add_error_details_stamp_listener' => [
            'class' => AddErrorDetailsStampSubscriber::class,
            'tags' => ['kernel.event_subscriber'],
        ],
    ],
]);
