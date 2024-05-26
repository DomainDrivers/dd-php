<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\ChosenResources;
use DomainDrivers\SmartSchedule\Planning\Demand;
use DomainDrivers\SmartSchedule\Planning\Demands;
use DomainDrivers\SmartSchedule\Planning\DemandsPerStage;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStages;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\Project;
use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\Infrastructure\TimeSlotNormalizer;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Set;

final class ProjectSerializer
{
    public function serialize(Project $project): string
    {
        return (string) \json_encode([
            'id' => $project->id()->toString(),
            'name' => $project->name(),
            'parallelizedStages' => array_map(
                fn (ParallelStages $stages) => array_map(
                    fn (Stage $stage): array => $this->mapStageToArray($stage),
                    $stages->stages()->toArray()),
                $project->parallelizedStages()->all()->toArray()
            ),
            'chosenResources' => [
                'resources' => array_map(fn (ResourceId $id): string => (string) $id, $project->chosenResources()->resources->toArray()),
                'time_slot' => TimeSlotNormalizer::normalize($project->chosenResources()->timeSlot),
            ],
            'demandsPerStage' => $this->mapDemandsPerStageToArray($project->demandsPerStage()->demands->toArray()),
            'demands' => array_map(fn (Demand $demand) => DemandNormalizer::normalize($demand), $project->demands()->all->toArray()),
            'schedule' => $this->mapScheduleToArray($project->schedule()->dates->toArray()),
        ]);
    }

    public function deserialize(string $project): Project
    {
        /** @var array{
         *     id: string,
         *     name: string,
         *     parallelizedStages: array<array<array{name: string, dependencies: mixed[], resources: string[], duration: int}>>,
         *     chosenResources: array{resources: string[], time_slot: array{from: string, to: string}},
         *     demandsPerStage: array<string, array<array{capability: array{name: string, type: string}}>>,
         *     demands: array<array{capability: array{name: string, type: string}, slot: array{from: string, to: string}}>,
         *     schedule: array<string, array{from: string, to: string}>
         * } $array */
        $array = \json_decode($project, true, flags: JSON_THROW_ON_ERROR);

        return Project::with(
            ProjectId::fromString($array['id']),
            $array['name'],
            ParallelStagesList::of(...array_map(
                fn (array $stages) => ParallelStages::of(...array_map(
                    fn (array $stage): Stage => $this->mapStageFromArray($stage),
                    $stages)),
                $array['parallelizedStages']
            )),
            new ChosenResources(
                Set::ofAll(array_map(fn (string $id): ResourceId => ResourceId::fromString($id), $array['chosenResources']['resources'])),
                TimeSlotNormalizer::denormalize($array['chosenResources']['time_slot'])
            ),
            new DemandsPerStage(Map::fromArray($this->mapDemandsFromArray($array['demandsPerStage']))),
            new Demands(GenericList::ofAll(array_map(
                fn (array $demand) => DemandNormalizer::denormalize($demand),
                $array['demands']
            ))),
            new Schedule(Map::fromArray($this->mapScheduleFromArray($array['schedule'])))
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
            'resources' => array_map(fn (ResourceId $id): string => (string) $id, $stage->resources()->toArray()),
            'duration' => $stage->duration()->seconds,
        ];
    }

    /**
     * @param array{name: string, dependencies: mixed[], resources: string[], duration: int} $stage
     */
    private function mapStageFromArray(array $stage): Stage
    {
        return new Stage(
            $stage['name'],
            Set::ofAll(array_map(fn (array $s) => $this->mapStageFromArray($s), $stage['dependencies'])), // @phpstan-ignore-line
            Set::ofAll(array_map(fn (string $id): ResourceId => ResourceId::fromString($id), $stage['resources'])),
            new Duration($stage['duration'])
        );
    }

    /**
     * @param array<string, Demands> $demands
     *
     * @return array<string, array<array{capability: array{name: string, type: string}}>>
     */
    private function mapDemandsPerStageToArray(array $demands): array
    {
        $array = [];
        foreach ($demands as $key => $value) {
            $array[$key] = array_map(fn (Demand $demand) => DemandNormalizer::normalize($demand), $value->all->toArray());
        }

        return $array;
    }

    /**
     * @param array<string, TimeSlot> $dates
     *
     * @return array<string, array{from: string, to: string}>
     */
    private function mapScheduleToArray(array $dates): array
    {
        $array = [];
        foreach ($dates as $key => $value) {
            $array[$key] = TimeSlotNormalizer::normalize($value);
        }

        return $array;
    }

    /**
     * @param array<string, array<array{capability: array{name: string, type: string}}>> $demands
     *
     * @return array<string, Demands>
     */
    private function mapDemandsFromArray(array $demands): array
    {
        $array = [];
        foreach ($demands as $key => $value) {
            $array[$key] = Demands::of(...array_map(fn (array $demand) => DemandNormalizer::denormalize($demand), $value));
        }

        return $array;
    }

    /**
     * @param array<string, array{from: string, to: string}> $dates
     *
     * @return array<string, TimeSlot>
     */
    private function mapScheduleFromArray(array $dates): array
    {
        $array = [];
        foreach ($dates as $key => $value) {
            $array[$key] = TimeSlotNormalizer::denormalize($value);
        }

        return $array;
    }
}
