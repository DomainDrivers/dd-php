<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\ChosenResources;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\TimeSlotNormalizer;
use Munus\Collection\Set;

final class ChosenResourcesType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof ChosenResources);

        return parent::convertToDatabaseValue($this->mapToArray($value), $platform);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ChosenResources
    {
        /** @var array{resources: string[], time_slot: array{from: string, to: string}} $array */
        $array = parent::convertToPHPValue($value, $platform);

        return $this->mapFromArray($array);
    }

    /**
     * @return mixed[]
     */
    private function mapToArray(ChosenResources $chosenResources): array
    {
        return [
            'resources' => array_map(fn (ResourceId $id): string => (string) $id, $chosenResources->resources->toArray()),
            'time_slot' => TimeSlotNormalizer::normalize($chosenResources->timeSlot),
        ];
    }

    /**
     * @param array{resources: string[], time_slot: array{from: string, to: string}} $array
     */
    private function mapFromArray(array $array): ChosenResources
    {
        return new ChosenResources(
            Set::ofAll(array_map(fn (string $id): ResourceId => ResourceId::fromString($id), $array['resources'])),
            TimeSlotNormalizer::denormalize($array['time_slot'])
        );
    }
}
