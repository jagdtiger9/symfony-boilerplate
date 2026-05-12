<?php

declare(strict_types=1);

namespace Aljerom\CqrsEvents\DomainEvents;

use DateTimeImmutable;

trait DomainEventTrait
{
    private string $aggregateRootId;
    private string $occurredOn;
    private ?string $actorId;

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
