<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\DomainEvents;

use DateTimeImmutable;

trait DomainEventTrait
{
    // Public so the Symfony Serializer can restore them when a stored event is
    // denormalized from the outbox: private properties have no setters, and the
    // constructor only receives the event payload.
    public string $aggregateRootId;
    public string $occurredOn;
    public ?string $actorId;

    protected function initEvent(string $aggregateRootId, ?string $actorId = null): void
    {
        $this->aggregateRootId = $aggregateRootId;
        $this->actorId = $actorId;
        $this->occurredOn = (new DateTimeImmutable())->format(DomainEventInterface::MICROSECOND_DATE_FORMAT);
    }

    public function getAggregateRootId(): string
    {
        return $this->aggregateRootId;
    }

    public function getOccurredOn(): string
    {
        return $this->occurredOn;
    }

    public function getActorId(): ?string
    {
        return $this->actorId;
    }
}
