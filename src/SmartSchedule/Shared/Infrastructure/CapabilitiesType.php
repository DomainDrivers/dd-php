<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use Munus\Collection\Set;

final class CapabilitiesType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof Set);

        /** @var Set<Capability> $value */

        return parent::convertToDatabaseValue($value->map(fn (Capability $c): array => CapabilityNormalizer::normalize($c))->toArray(), $platform);
    }

    /**
     * @return Set<Capability>
     */
    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Set
    {
        /** @var array<array{name: string, type: string}> $array */
        $array = parent::convertToPHPValue($value, $platform);

        return Set::ofAll(array_map(fn (array $a) => CapabilityNormalizer::denormalize($a), $array));
    }
}
