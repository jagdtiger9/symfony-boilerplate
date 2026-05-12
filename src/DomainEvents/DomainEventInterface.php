<?php

declare(strict_types=1);

namespace Aljerom\CqrsEvents\DomainEvents;

interface DomainEventInterface
{
    public const string MICROSECOND_DATE_FORMAT = 'Y-m-d\TH:i:s.uP';

    public function getAggregateRootId(): string;

    public function getOccurredOn(): string;

    public function getActorId(): ?string;
}
