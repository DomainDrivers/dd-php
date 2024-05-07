<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared;

final readonly class ResourceName
{
    public function __construct(public string $name)
    {
    }
}
