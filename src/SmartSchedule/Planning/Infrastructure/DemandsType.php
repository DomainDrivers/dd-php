<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Planning\Demand;
use DomainDrivers\SmartSchedule\Planning\Demands;
use Munus\Collection\GenericList;

final class DemandsType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof Demands);

        return parent::convertToDatabaseValue(
            array_map(fn (Demand $demand) => DemandNormalizer::normalize($demand), $value->all->toArray()),
            $platform
        );
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Demands
    {
        /** @var array<array{capability: array{name: string, type: string}, slot: array{from: string, to: string}}> $array */
        $array = parent::convertToPHPValue($value, $platform);

        return new Demands(GenericList::ofAll(array_map(
            fn (array $demand) => DemandNormalizer::denormalize($demand),
            $array
        )));
    }
}
