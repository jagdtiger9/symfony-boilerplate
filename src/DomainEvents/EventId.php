<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\DomainEvents;

use Ramsey\Uuid\UuidInterface;

final class EventId
{
    private function __construct(private readonly UuidInterface $id)
    {
    }

    public static function fromString(string $id): self
    {
        return new self(\Ramsey\Uuid\Uuid::fromString($id));
    }

    public static function fromUuid(UuidInterface $uuid): self
    {
        return new self($uuid);
    }

    public function asString(): string
    {
        return $this->id->toString();
    }

    public function equals(self $other): bool
    {
        return $this->id->toString() === $other->id->toString();
    }

    public function __toString(): string
    {
        return $this->id->toString();
    }
}
