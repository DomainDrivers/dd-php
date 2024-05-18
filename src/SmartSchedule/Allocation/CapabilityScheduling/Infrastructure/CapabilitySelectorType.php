<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilitySelector;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\SelectingPolicy;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\CapabilityNormalizer;
use Munus\Collection\Set;

final class CapabilitySelectorType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof CapabilitySelector);

        return parent::convertToDatabaseValue([
            'capabilities' => $value->capabilities->map(fn (Capability $c): array => CapabilityNormalizer::normalize($c))->toArray(),
            'selecting_policy' => $value->selectingPolicy->value,
        ], $platform);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): CapabilitySelector
    {
        /** @var array{capabilities: array<array{name: string, type: string}>, selecting_policy: string} $array */
        $array = parent::convertToPHPValue($value, $platform);

        return new CapabilitySelector(
            Set::ofAll(array_map(fn (array $a) => CapabilityNormalizer::denormalize($a), $array['capabilities'])),
            SelectingPolicy::from($array['selecting_policy'])
        );
    }
}
