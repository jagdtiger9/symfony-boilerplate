<?php

declare(strict_types=1);

namespace Aljerom\CqrsEvents\DomainEvents;

use DateTimeImmutable;

interface EventRepositoryInterface
{
    public function nextIdentity(): EventId;

    public function append(DomainEventInterface $domainEvent): void;

    public function replace(DomainEventInterface $domainEvent): void;

    public function publish(StoredEvent $storedEvent): void;

    /** @return StoredEvent[] */
    public function getUnpublished(DateTimeImmutable $dateTime): array;
}
