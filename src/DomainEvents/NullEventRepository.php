<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\DomainEvents;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * No-op default for EventRepositoryInterface, bound by the bundle so a
 * consuming project compiles without an event store; replace the binding
 * with a real implementation (e.g. via AbstractDoctrineEventRepository).
 */
final class NullEventRepository implements EventRepositoryInterface
{
    public function nextIdentity(): EventId
    {
        return EventId::fromUuid(Uuid::uuid4());
    }

    public function append(DomainEventInterface $domainEvent): void
    {
    }

    public function replace(DomainEventInterface $domainEvent): void
    {
    }

    public function publish(StoredEvent $storedEvent): void
    {
    }

    public function getUnpublished(DateTimeImmutable $dateTime): array
    {
        return [];
    }
}
