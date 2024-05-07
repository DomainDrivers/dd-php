<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Planning\Demand;
use DomainDrivers\SmartSchedule\Planning\Demands;
use DomainDrivers\SmartSchedule\Planning\DemandsPerStage;
use Munus\Collection\Map;

final class DemandsPerStageType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof DemandsPerStage);

        return parent::convertToDatabaseValue($this->mapToArray($value->demands->toArray()), $platform);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): DemandsPerStage
    {
        /** @var array<string, array<array{capability: array{name: string, type: string}}>> $array */
        $array = parent::convertToPHPValue($value, $platform);

        return new DemandsPerStage(Map::fromArray($this->mapFromArray($array)));
    }

    /**
     * @param array<string, Demands> $demands
     *
     * @return array<string, array<array{capability: array{name: string, type: string}}>>
     */
    private function mapToArray(array $demands): array
    {
        $array = [];
        foreach ($demands as $key => $value) {
            $array[$key] = array_map(fn (Demand $demand) => DemandNormalizer::normalize($demand), $value->all->toArray());
        }

        return $array;
    }

    /**
     * @param array<string, array<array{capability: array{name: string, type: string}}>> $demands
     *
     * @return array<string, Demands>
     */
    private function mapFromArray(array $demands): array
    {
        $array = [];
        foreach ($demands as $key => $value) {
            $array[$key] = Demands::of(...array_map(fn (array $demand) => DemandNormalizer::denormalize($demand), $value));
        }

        return $array;
    }
}
