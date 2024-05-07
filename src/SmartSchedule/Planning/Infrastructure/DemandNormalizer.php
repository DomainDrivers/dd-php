<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use DomainDrivers\SmartSchedule\Planning\Demand;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;

final class DemandNormalizer
{
    /**
     * @return array{capability: array{name: string, type: string}}
     */
    public static function normalize(Demand $demand): array
    {
        return [
            'capability' => [
                'name' => $demand->capability->name,
                'type' => $demand->capability->type,
            ],
        ];
    }

    /**
     * @param array{capability: array{name: string, type: string}} $array
     */
    public static function denormalize(array $array): Demand
    {
        return new Demand(
            new Capability($array['capability']['name'], $array['capability']['type'])
        );
    }
}
