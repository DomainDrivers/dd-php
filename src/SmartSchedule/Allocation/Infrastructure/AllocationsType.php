<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Allocation\AllocatedCapability;
use DomainDrivers\SmartSchedule\Allocation\Allocations;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\CapabilitySelector;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\CapabilityNormalizer;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\TimeSlotNormalizer;
use DomainDrivers\SmartSchedule\Shared\SelectingPolicy;
use Munus\Collection\Set;

final class AllocationsType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof Allocations);

        return parent::convertToDatabaseValue($value->all->map(fn (AllocatedCapability $a): array => [
            'id' => $a->allocatedCapabilityID->toString(),
            'capability' => [
                'capabilities' => array_map(fn (Capability $c) => CapabilityNormalizer::normalize($c), $a->capability->capabilities->toArray()),
                'selecting_policy' => $a->capability->selectingPolicy->value,
            ],
            'time_slot' => TimeSlotNormalizer::normalize($a->timeSlot),
        ])->toArray(), $platform);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Allocations
    {
        /** @var array<array{id: string, resource_id: string, capability: array{capabilities: array<array{name: string, type: string}>, selecting_policy: string}, time_slot: array{from: string, to: string}}> $array */
        $array = parent::convertToPHPValue($value, $platform);

        return new Allocations(Set::ofAll(array_map(fn (array $a) => new AllocatedCapability(
            AllocatableCapabilityId::fromString($a['id']),
            new CapabilitySelector(
                Set::ofAll(array_map(fn (array $c) => CapabilityNormalizer::denormalize($c), $a['capability']['capabilities'])),
                SelectingPolicy::from($a['capability']['selecting_policy'])
            ),
            TimeSlotNormalizer::denormalize($a['time_slot'])
        ), $array)));
    }
}
