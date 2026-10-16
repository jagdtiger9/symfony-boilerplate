# Symfony Boilerplate

A Symfony bundle that provides the DDD/CQRS scaffolding shared across projects:

- **CQRS buses** on top of Symfony Messenger — `Command`/`Query` marker interfaces and thin buses that unwrap `HandlerFailedException`.
- **Request value resolver** — controller arguments typed as `Command`/`Query` are automatically hydrated from the request and validated.
- **Event subscribers** — timezone/locale/request-id setup, JSON body decoding, validation error responses, console dumper, Messenger error stamps, domain event publishing.
- **Domain events** — an event store backed by Doctrine that captures events from aggregate roots on flush and publishes them at request/command termination.

## Requirements

- PHP >= 8.4
- Symfony 7.3+ or 8.x
- Doctrine ORM 3

## Installation

```bash
composer require aljerom/symfony-boilerplate
```

If you don't use Symfony Flex, register the bundle in `config/bundles.php`:

```php
return [
    // ...
    Aljerom\SymfonyBoilerplate\SymfonyBoilerplateBundle::class => ['all' => true],
];
```

## Configuration

```php
<?php
// config/packages/symfony_boilerplate.php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('symfony_boilerplate', [
        'timezone' => 'UTC', // default timezone, applied on every request and console command
        'locale' => 'en',    // default locale
    ]);
};
```

## CQRS buses

Mark your application messages with the bundle's marker interfaces:

```php
use Aljerom\SymfonyBoilerplate\CQRS\Command;

final class ChangePasswordCommand implements Command
{
    public function __construct(
        public readonly string $userId,
        public readonly string $newPassword,
    ) {
    }
}
```

The bundle registers `CommandBus` and `QueryBus` interfaces backed by Symfony Messenger. The `DispatchTrait` unwraps `HandlerFailedException`, so the original exception thrown by a handler (e.g. a domain exception) reaches the caller directly instead of the Messenger wrapper.

```php
final class UserController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
    }

    public function changePassword(ChangePasswordCommand $command): JsonResponse
    {
        $this->commandBus->dispatch($command);

        return $this->json(['success' => true]);
    }
}
```

The buses are injected by name, so the consuming project must define buses named `command`, `query` and `event` in `config/packages/messenger.php`:

```php
$container->extension('framework', [
    'messenger' => [
        'default_bus' => 'command.bus',
        'buses' => [
            'command.bus' => ['middleware' => ['doctrine_transaction']],
            'query.bus' => [],
            'event.bus' => [
                'default_middleware' => ['enabled' => true, 'allow_no_handlers' => true],
            ],
        ],
    ],
]);
```

Register handlers as regular Messenger handlers (`#[AsMessageHandler]`), one use case per handler.

## Request value resolver

`MessageValueResolver` is a Symfony [value resolver](https://symfony.com/doc/current/controller/value_resolver.html): any controller argument whose type implements `Command` or `Query` is resolved automatically — no manual `Request` plumbing or DTO construction in controllers.

For each resolved argument the resolver:

1. Collects parameters from the request body (POST/PUT), the query string (other methods), route parameters and uploaded files.
2. Hydrates the message with `aljerom/simple-hydrator`.
3. Validates it with the Symfony Validator.

On validation failure it throws `ValidationException`, which `ValidationExceptionListener` converts into a `400` JSON response (see below).

```php
final class ChangePasswordCommand implements Command
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $currentPassword,
        #[Assert\NotBlank]
        #[Assert\Length(min: 4)]
        public readonly string $newPassword,
    ) {
    }
}
```

```php
// POST /users/42/change-password  {"currentPassword":"...","newPassword":"..."}
public function changePassword(string $id, ChangePasswordCommand $command): JsonResponse
```

JSON request bodies are decoded into request parameters by `KernelRequestSubscriber` before the resolver runs, so the example above works with `application/json` requests out of the box. Invalid JSON gets a `400` response immediately.

The resolver is registered by the bundle with `autoconfigure`, so no extra wiring is needed.

## Event subscribers

### KernelRequestSubscriber

Listens to `KernelEvents::REQUEST` and performs request setup in priority order (locale → JSON body → request id → timezone):

- **Locale** — if the route has a `_locale` attribute, stores it in the session; otherwise restores the locale from the session (falling back to the configured default).
- **JSON payload** — for requests with JSON content type, decodes the body into the request parameters. Malformed JSON is answered immediately with a `400` JSON response.
- **Unique request ID** — attaches a UUIDv7 to the request attributes under `_request.unique.id` (correlates logs and stored domain events within one request).
- **Default timezone** — sets the PHP default timezone to the configured value.

### ResponseSubscriber

Listens to `KernelEvents::RESPONSE`. In debug mode only, sets the `Symfony-Debug-Toolbar-Replace: 1` header on AJAX responses so the web profiler toolbar is injected into XHR content.

### ConsoleDumperSubscriber

Listens to `ConsoleEvents::COMMAND`. Sets the default timezone and installs a `VarDumper` handler so `dump()` output is rendered in the console (`CliDumper` for CLI SAPI, `HtmlDumper` otherwise) instead of being written to `var/log` files.

### ValidationExceptionListener

Listens to `KernelEvents::EXCEPTION`. Converts `ValidationException` (thrown by the value resolver) into a `400` JSON response:

```json
{
    "message": "...",
    "errors": [
        {"property": "newPassword", "value": "", "message": "This value should not be blank."}
    ]
}
```

### AddErrorDetailsStampSubscriber

Listens to `WorkerMessageFailedEvent`. Replaces Symfony's default `messenger.failure.add_error_details_stamp_listener` (registered under that exact service id) and adds an `ErrorDetailsStamp` containing only the exception class, code and message — without the `FlattenException` and its full stack trace, which causes oversized messages on AMQP.

### DomainEventPublishSubscriber

Listens to `KernelEvents::TERMINATE` and `ConsoleEvents::TERMINATE`. Publishes domain events stored during the request/command run: reads unpublished events from the event repository, deserializes them and dispatches each on the `event.bus`, then removes them from the store. Skips silently when the entity manager is closed or the events table is unavailable.

## Domain events

Domain events implement an outbox pattern: aggregate roots record events while changing state, a Doctrine `onFlush` listener persists them in the same transaction as the aggregate, and a terminate subscriber publishes them after the response is sent.

### What the bundle provides

| Piece | Type | Purpose |
|---|---|---|
| `DomainEventInterface` | interface | Contract for domain events: `getAggregateRootId()`, `getOccurredOn()`, `getActorId()` |
| `DomainEventTrait` | trait | Reference implementation of the contract; call `initEvent()` in the constructor |
| `ReplaceableDomainEventInterface` | marker interface | Events that replace previous unpublished events of the same class and aggregate root instead of appending |
| `DomainEventAwareInterface` | interface | Contract for aggregate roots: `recordEvent()`, `recordEventOnce()`, `releaseEvents()`, `resetEvents()` |
| `EventStoreTrait` | trait | Reference implementation of the aggregate root contract |
| `EventId` | value object | UUID wrapper for stored event identities |
| `StoredEvent` | abstract class | Base class for your persistence entity |
| `EventRepositoryInterface` | interface | Event store contract: `append()`, `replace()`, `publish()`, `getUnpublished()` |
| `NullEventRepository` | class | No-op implementation bound to `EventRepositoryInterface` by default, so a fresh project compiles without an event store; replace the binding with a real implementation |
| `AbstractDoctrineEventRepository` | abstract class | Doctrine implementation of the repository |
| `PersistDomainEventListener` | Doctrine listener | Registered by the bundle (`onFlush`, priority 500, all connections) — captures events from scheduled `DomainEventAwareInterface` entities |
| `DomainEventPublishSubscriber` | subscriber | Registered by the bundle — publishes stored events at terminate |

### What you implement

**1. Domain event classes** — implement `DomainEventInterface`, or simply use `DomainEventTrait`:

```php
use Aljerom\SymfonyBoilerplate\DomainEvents\DomainEventInterface;
use Aljerom\SymfonyBoilerplate\DomainEvents\DomainEventTrait;

final class UserPasswordChanged implements DomainEventInterface
{
    use DomainEventTrait;

    public function __construct(
        string $userId,
        private readonly string $newPasswordHash,
        ?string $actorId = null,
    ) {
        $this->initEvent($userId, $actorId);
    }
}
```

Events are serialized with the Symfony Serializer, so they must be (de)serializable with the default normalizers — public getters for payload fields, no constructor arguments the serializer can't satisfy after deserialization.

For events that represent a current state rather than a history entry (e.g. "latest known value"), implement `ReplaceableDomainEventInterface` instead — the store keeps only the latest unpublished instance per class and aggregate root.

**2. Aggregate roots** — implement `DomainEventAwareInterface` (use `EventStoreTrait`) and record events on state changes:

```php
use Aljerom\SymfonyBoilerplate\DomainEvents\DomainEventAwareInterface;
use Aljerom\SymfonyBoilerplate\DomainEvents\EventStoreTrait;

class User implements DomainEventAwareInterface
{
    use EventStoreTrait;

    public function changePassword(string $newPasswordHash): void
    {
        // ... mutate state
        $this->recordEvent(new UserPasswordChanged($this->id, $newPasswordHash));
    }
}
```

`PersistDomainEventListener` picks up every scheduled entity implementing this interface and persists its released events.

**3. A `StoredEvent` entity** — create a Doctrine entity extending `StoredEvent`. The `publishedOn` field must default to `0` — that is the "unpublished" marker the repository queries against.

```php
use Aljerom\SymfonyBoilerplate\DomainEvents\EventId;
use Aljerom\SymfonyBoilerplate\DomainEvents\StoredEvent;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'domain_events')]
class DomainEvent extends StoredEvent
{
    #[ORM\Column(type: 'bigint')]
    private int $publishedOn = 0;

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'string', length: 36)]
        private string $eventId,
        #[ORM\Column(type: 'string', length: 255)]
        private string $typeName,
        #[ORM\Column(type: 'datetime_immutable')]
        private DateTimeImmutable $occurredOn,
        #[ORM\Column(type: 'string', length: 36)]
        private string $aggregateRoot,
        #[ORM\Column(type: 'text')]
        private string $eventBody,
        #[ORM\Column(type: 'string', length: 36)]
        private string $requestId,
    ) {
    }

    public function getEventId(): EventId
    {
        return EventId::fromString($this->eventId);
    }

    // ... getters for typeName, occurredOn, aggregateRoot, eventBody, requestId

    public function setPublishedOn(DateTimeImmutable $publishedOn): void
    {
        $this->publishedOn = $publishedOn->getTimestamp();
    }
}
```

**4. An event repository** — extend `AbstractDoctrineEventRepository` and implement the two remaining pieces: the entity class and the `StoredEvent` factory.

```php
use Aljerom\SymfonyBoilerplate\DomainEvents\Doctrine\AbstractDoctrineEventRepository;
use Aljerom\SymfonyBoilerplate\DomainEvents\EventId;
use Aljerom\SymfonyBoilerplate\DomainEvents\StoredEvent;
use DateTimeImmutable;

final class DoctrineEventRepository extends AbstractDoctrineEventRepository
{
    protected function getEntityClass(): string
    {
        return DomainEvent::class;
    }

    protected function createStoredEvent(
        EventId $eventId,
        string $typeName,
        DateTimeImmutable $occurredOn,
        string $aggregateRoot,
        string $eventBody,
        string $requestId,
    ): StoredEvent {
        return new DomainEvent(
            eventId: (string) $eventId,
            typeName: $typeName,
            occurredOn: $occurredOn,
            aggregateRoot: $aggregateRoot,
            eventBody: $eventBody,
            requestId: $requestId,
        );
    }
}
```

**5. Wire the interface** — in `config/services.php`:

```php
use Aljerom\SymfonyBoilerplate\DomainEvents\EventRepositoryInterface;
use App\Infrastructure\Persistence\DoctrineEventRepository;

$services->alias(EventRepositoryInterface::class, DoctrineEventRepository::class);
```

The bundle binds `EventRepositoryInterface` to the no-op `NullEventRepository` by default, so a fresh project compiles and runs without an event store — recorded events are simply discarded until the binding is replaced. The alias above overrides that default: app-level configuration is loaded before the bundle applies its default binding, so the app's alias always wins.

The bundle already registers `PersistDomainEventListener` and `DomainEventPublishSubscriber`; apart from this alias, the only other application-side wiring is the `event.bus` from the Messenger configuration above.

**6. Event handlers** — domain events are dispatched on `event.bus` as regular Messenger messages; handle them with `#[AsMessageHandler]`.

### How it works end to end

1. A command handler loads an aggregate root, which records domain events via `EventStoreTrait`.
2. On Doctrine flush, `PersistDomainEventListener` releases the events and persists them through the repository in the same transaction.
3. After the response is sent (or the console command finishes), `DomainEventPublishSubscriber` reads the events stored during this request, deserializes and dispatches them on `event.bus`, and removes them from the store.

Stored events are scoped to the request that created them (each repository instance generates a request UUID), so `getUnpublished()` only ever returns events produced by the current request/command run.

## License

MIT
