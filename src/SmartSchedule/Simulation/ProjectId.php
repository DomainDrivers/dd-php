<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Simulation;

use Symfony\Component\Uid\Uuid;

final readonly class ProjectId
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

    public function toString(): string
    {
        return $this->id->toRfc4122();
    }
}
