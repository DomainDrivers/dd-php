<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\TimeSlotNormalizer;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Map;

final class ScheduleType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof Schedule);

        return parent::convertToDatabaseValue($this->mapToArray($value->dates->toArray()), $platform);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Schedule
    {
        /** @var array<string, array{from: string, to: string}> $array */
        $array = parent::convertToPHPValue($value, $platform);

        return new Schedule(Map::fromArray($this->mapFromArray($array)));
    }

    /**
     * @param array<string, TimeSlot> $dates
     *
     * @return array<string, array{from: string, to: string}>
     */
    private function mapToArray(array $dates): array
    {
        $array = [];
        foreach ($dates as $key => $value) {
            $array[$key] = TimeSlotNormalizer::normalize($value);
        }

        return $array;
    }

    /**
     * @param array<string, array{from: string, to: string}> $dates
     *
     * @return array<string, TimeSlot>
     */
    private function mapFromArray(array $dates): array
    {
        $array = [];
        foreach ($dates as $key => $value) {
            $array[$key] = TimeSlotNormalizer::denormalize($value);
        }

        return $array;
    }
}
