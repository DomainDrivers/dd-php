<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Stream\Collectors;

class Project
{
    private ProjectId $id;

    private string $name;

    private ParallelStagesList $parallelizedStages;

    private ChosenResources $chosenResources;

    private DemandsPerStage $demandsPerStage;

    private Demands $demands;

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

    public static function with(
        ProjectId $id,
        string $name,
        ParallelStagesList $parallelizedStages,
        ChosenResources $chosenResources,
        DemandsPerStage $demandsPerStage,
        Demands $demands,
        Schedule $schedule
    ): self {
        $instance = new self($name, $parallelizedStages);
        $instance->id = $id;
        $instance->chosenResources = $chosenResources;
        $instance->demandsPerStage = $demandsPerStage;
        $instance->demands = $demands;
        $instance->schedule = $schedule;

        return $instance;
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
