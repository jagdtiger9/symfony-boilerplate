<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\DomainEvents;

use DateTimeImmutable;

abstract class StoredEvent
{
    abstract public function getEventId(): EventId;

    abstract public function getTypeName(): string;

    abstract public function getOccurredOn(): DateTimeImmutable;

    abstract public function getAggregateRoot(): string;

    abstract public function getEventBody(): string;

    abstract public function getRequestId(): string;

    abstract public function setPublishedOn(DateTimeImmutable $publishedOn): void;
}
