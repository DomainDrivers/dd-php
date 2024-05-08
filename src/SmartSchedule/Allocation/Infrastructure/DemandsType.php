<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\CapabilityNormalizer;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\TimeSlotNormalizer;

final class DemandsType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof Demands);

        return parent::convertToDatabaseValue($value->all->map(fn (Demand $demand) => [
            'capability' => CapabilityNormalizer::normalize($demand->capability),
            'time_slot' => TimeSlotNormalizer::normalize($demand->slot),
        ])->toArray(), $platform);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Demands
    {
        /** @var array<array{capability: array{name: string, type: string}, time_slot: array{from: string, to: string}}> $array */
        $array = parent::convertToPHPValue($value, $platform);

        return Demands::of(...array_map(fn (array $demand): Demand => new Demand(
            CapabilityNormalizer::denormalize($demand['capability']),
            TimeSlotNormalizer::denormalize($demand['time_slot'])
        ), $array));
    }
}
