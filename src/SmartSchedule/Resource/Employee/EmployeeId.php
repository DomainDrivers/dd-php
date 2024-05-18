<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Resource\Employee;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableResourceId;
use Symfony\Component\Uid\Uuid;

final readonly class EmployeeId implements \Stringable
{
    private function __construct(public Uuid $id)
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

    public function toAllocatableResourceId(): AllocatableResourceId
    {
        return new AllocatableResourceId($this->id);
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
