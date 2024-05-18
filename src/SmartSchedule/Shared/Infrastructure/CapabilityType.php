<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Shared\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;

final class CapabilityType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof Capability);

        return parent::convertToDatabaseValue(CapabilityNormalizer::normalize($value), $platform);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Capability
    {
        /** @var array{name: string, type: string} $array */
        $array = parent::convertToPHPValue($value, $platform);

        return CapabilityNormalizer::denormalize($array);
    }
}
