<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStages;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Shared\ResourceName;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use Munus\Collection\Set;

final class ParallelStagesListType extends JsonType
{
    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        \assert($value instanceof ParallelStagesList);

        return parent::convertToDatabaseValue($this->mapToArray($value->all()->toArray()), $platform);
    }

    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ParallelStagesList
    {
        /** @var array<array<array{name: string, dependencies: mixed[], resources: string[], duration: int}>> $array */
        $array = parent::convertToPHPValue($value, $platform);

        return ParallelStagesList::of(...$this->mapFromArray($array));
    }

    /**
     * @param array<ParallelStages> $parallelStages
     *
     * @return array<array<array{name: string, dependencies: mixed[], resources: string[], duration: int}>>
     */
    private function mapToArray(array $parallelStages): array
    {
        return array_map(
            fn (ParallelStages $stages) => array_map(
                fn (Stage $stage): array => $this->mapStageToArray($stage),
                $stages->stages()->toArray()),
            $parallelStages
        );
    }

    /**
     * @return array{name: string, dependencies: mixed[], resources: string[], duration: int}
     */
    private function mapStageToArray(Stage $stage): array
    {
        return [
            'name' => $stage->name(),
            'dependencies' => array_map(fn (Stage $s) => $this->mapStageToArray($s), $stage->dependencies()->toArray()),
            'resources' => array_map(fn (ResourceName $name) => $name->name, $stage->resources()->toArray()),
            'duration' => $stage->duration()->seconds,
        ];
    }

    /**
     * @param array<array<array{name: string, dependencies: mixed[], resources: string[], duration: int}>> $parallelStages
     *
     * @return array<ParallelStages>
     */
    private function mapFromArray(array $parallelStages): array
    {
        return array_map(
            fn (array $stages) => ParallelStages::of(...array_map(
                fn (array $stage): Stage => $this->mapStageFromArray($stage),
                $stages)),
            $parallelStages
        );
    }

    /**
     * @param array{name: string, dependencies: mixed[], resources: string[], duration: int} $stage
     */
    private function mapStageFromArray(array $stage): Stage
    {
        return new Stage(
            $stage['name'],
            Set::ofAll(array_map(fn (array $s) => $this->mapStageFromArray($s), $stage['dependencies'])), // @phpstan-ignore-line
            Set::ofAll(array_map(fn (string $name) => new ResourceName($name), $stage['resources'])),
            new Duration($stage['duration'])
        );
    }
}
