<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\SmartSchedule\Simulation\Demand as SimulationDemand;
use DomainDrivers\SmartSchedule\Simulation\Demands as SimulationDemands;
use DomainDrivers\SmartSchedule\Simulation\ProjectId;
use DomainDrivers\SmartSchedule\Simulation\SimulatedProject;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Stream\Collectors;
use Symfony\Component\Uid\Uuid;

final readonly class Projects
{
    /**
     * @param Map<string, Project> $projects
     */
    public function __construct(public Map $projects)
    {
    }

    public function transfer(Uuid $projectFrom, Uuid $projectTo, AllocatedCapability $capability, TimeSlot $forSlot): self
    {
        $from = $this->projects->get($projectFrom->toRfc4122());
        $to = $this->projects->get($projectTo->toRfc4122());

        if ($from->isEmpty() || $to->isEmpty()) {
            return $this;
        }

        $removed = $from->get()->remove($capability, $forSlot);
        if ($removed->isEmpty()) {
            return $this;
        }

        $to->get()->add(AllocatedCapability::with($removed->get()->resourceId, $removed->get()->capability, $forSlot));

        return new self($this->projects);
    }

    /**
     * @return GenericList<SimulatedProject>
     */
    public function toSimulatedProjects(): GenericList
    {
        return $this->projects->toStream()->map(function ($t) {
            /** @var array{0: string, 1: Project} $t */
            return new SimulatedProject(
                ProjectId::fromString($t[0]),
                fn () => $t[1]->earnings(),
                new SimulationDemands($t[1]->missingDemands()->all->map(fn (Demand $d) => SimulationDemand::for($d->capability, $d->slot)))
            );
        })->collect(Collectors::toList());
    }
}
