<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Version;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Stream\Collectors;

#[Entity]
#[Table(name: 'projects')]
class Project
{
    #[Id]
    #[Column(type: 'project_id')]
    private ProjectId $id;

    #[Version]
    #[Column(type: 'bigint')]
    private int $version;

    #[Column(type: 'string')]
    private string $name;

    #[Column(type: 'parallel_stages_list', options: ['jsonb' => true])]
    private ParallelStagesList $parallelizedStages;

    #[Column(type: 'chosen_resources', options: ['jsonb' => true])]
    private ChosenResources $chosenResources;

    #[Column(type: 'demands_per_stage', options: ['jsonb' => true])]
    private DemandsPerStage $demandsPerStage;

    #[Column(type: 'demands', options: ['jsonb' => true])]
    private Demands $demands;

    #[Column(type: 'schedule', options: ['jsonb' => true])]
    private Schedule $schedule;

    public function __construct(string $name, ParallelStagesList $parallelizedStages)
    {
        $this->id = ProjectId::newOne();
        $this->name = $name;
        $this->parallelizedStages = $parallelizedStages;
        $this->chosenResources = ChosenResources::none();
        $this->demandsPerStage = DemandsPerStage::empty();
        $this->demands = Demands::none();
        $this->schedule = Schedule::none();
    }

    public function addDemands(Demands $demands): void
    {
        $this->demands = $this->demands->add($demands);
    }

    public function addScheduleOnStartDate(\DateTimeImmutable $possibleStartDate): void
    {
        $this->schedule = Schedule::basedOnStartDay($possibleStartDate, $this->parallelizedStages);
    }

    public function addScheduleOnStageTimeSlot(Stage $criticalStage, TimeSlot $stageTimeSlot): void
    {
        $this->schedule = Schedule::basedOnReferenceStageTimeSlot($criticalStage, $stageTimeSlot, $this->parallelizedStages);
    }

    public function addSchedule(Schedule $schedule): void
    {
        $this->schedule = $schedule;
    }

    public function addChosenResources(ChosenResources $neededResources): void
    {
        $this->chosenResources = $neededResources;
    }

    public function defineStages(ParallelStagesList $parallelStagesList): void
    {
        $this->parallelizedStages = $parallelStagesList;
    }

    public function addDemandsPerStage(DemandsPerStage $demandsPerStage): void
    {
        $this->demandsPerStage = $demandsPerStage;
        /** @var Demand[] $uniqueDemands */
        $uniqueDemands = $demandsPerStage->demands->values()->flatMap(fn (Demands $demands) => $demands->all)->collect(Collectors::toSet())->toArray();

        $this->addDemands(Demands::of(...$uniqueDemands));
    }

    public function id(): ProjectId
    {
        return $this->id;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function parallelizedStages(): ParallelStagesList
    {
        return $this->parallelizedStages;
    }

    public function chosenResources(): ChosenResources
    {
        return $this->chosenResources;
    }

    public function demandsPerStage(): DemandsPerStage
    {
        return $this->demandsPerStage;
    }

    public function demands(): Demands
    {
        return $this->demands;
    }

    public function schedule(): Schedule
    {
        return $this->schedule;
    }
}
