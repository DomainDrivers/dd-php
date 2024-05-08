<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Allocation\AllocatedCapability;
use DomainDrivers\SmartSchedule\Allocation\Allocations;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\CapabilityNormalizer;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\TimeSlotNormalizer;
use Munus\Collection\Set;
use Symfony\Component\Uid\Uuid;

final class AllocationsType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof Allocations);

        return parent::convertToDatabaseValue($value->all->map(fn (AllocatedCapability $a): array => [
            'id' => $a->allocatedCapabilityID->toRfc4122(),
            'resource_id' => $a->resourceId->toRfc4122(),
            'capability' => CapabilityNormalizer::normalize($a->capability),
            'time_slot' => TimeSlotNormalizer::normalize($a->timeSlot),
        ])->toArray(), $platform);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Allocations
    {
        /** @var array<array{id: string, resource_id: string, capability: array{name: string, type: string}, time_slot: array{from: string, to: string}}> $array */
        $array = parent::convertToPHPValue($value, $platform);

        return new Allocations(Set::ofAll(array_map(fn (array $a) => new AllocatedCapability(
            Uuid::fromString($a['id']),
            Uuid::fromString($a['resource_id']),
            CapabilityNormalizer::denormalize($a['capability']),
            TimeSlotNormalizer::denormalize($a['time_slot'])
        ), $array)));
    }
}
