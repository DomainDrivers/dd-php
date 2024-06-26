<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

use Symfony\Component\Uid\Uuid;

final readonly class AllocatableResourceId implements \Stringable
{
    public function __construct(public Uuid $id)
    {
    }

    public static function newOne(): self
    {
        return new self(Uuid::v7());
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public function toString(): string
    {
        return $this->id->toRfc4122();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->toString();
    }
}
