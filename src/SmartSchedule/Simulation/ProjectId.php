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

    public function toString(): string
    {
        return $this->id->toRfc4122();
    }
}
