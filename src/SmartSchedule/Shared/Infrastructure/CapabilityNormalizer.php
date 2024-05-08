<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use DomainDrivers\SmartSchedule\Shared\Capability\Capability;

final class CapabilityNormalizer
{
    /**
     * @return array{name: string, type: string}
     */
    public static function normalize(Capability $capability): array
    {
        return [
            'name' => $capability->name,
            'type' => $capability->type,
        ];
    }

    /**
     * @param array{name: string, type: string} $capability
     */
    public static function denormalize(array $capability): Capability
    {
        return new Capability($capability['name'], $capability['type']);
    }
}
