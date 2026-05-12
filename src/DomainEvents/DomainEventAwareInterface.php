<?php

declare(strict_types=1);

namespace Aljerom\CqrsEvents\DomainEvents;

interface DomainEventAwareInterface
{
    public function recordEvent(DomainEventInterface $event): void;

    public function recordEventOnce(DomainEventInterface $event): void;

    /** @return DomainEventInterface[] */
    public function releaseEvents(): array;

    public function resetEvents(): void;
}
