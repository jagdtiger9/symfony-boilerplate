<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\DomainEvents;

trait EventStoreTrait
{
    /** @var DomainEventInterface[] */
    private array $domainEvents = [];

    public function recordEvent(DomainEventInterface $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function recordEventOnce(DomainEventInterface $event): void
    {
        foreach ($this->domainEvents as $recorded) {
            if ($recorded::class === $event::class) {
                return;
            }
        }
        $this->domainEvents[] = $event;
    }

    public function releaseEvents(): array
    {
        $events = array_values($this->domainEvents);
        $this->domainEvents = [];
        return $events;
    }

    public function resetEvents(): void
    {
        $this->domainEvents = [];
    }
}
