<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;

final readonly class Demand
{
    public function __construct(public Capability $capability)
    {
    }

    public static function forSkill(string $name): self
    {
        return new self(Capability::skill($name));
    }

    public static function forAsset(string $name): self
    {
        return new self(Capability::asset($name));
    }

    public static function forPermission(string $name): self
    {
        return new self(Capability::permission($name));
    }
}
