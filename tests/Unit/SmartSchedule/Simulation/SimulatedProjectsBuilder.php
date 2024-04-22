<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Simulation;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Simulation\Demand;
use DomainDrivers\SmartSchedule\Simulation\Demands;
use DomainDrivers\SmartSchedule\Simulation\ProjectId;
use DomainDrivers\SmartSchedule\Simulation\SimulatedProject;
use Munus\Collection\GenericList;
use Munus\Collection\Map;

final class SimulatedProjectsBuilder
{
    private ProjectId $currentId;

    /**
     * @var GenericList<ProjectId>
     */
    private GenericList $simulatedProjects;

    /**
     * @var Map<string, Demands>
     */
    private Map $simulatedDemands;

    /**
     * @var Map<string, Decimal>
     */
    private Map $simulatedEarnings;

    public function __construct()
    {
    }

    public function withProject(ProjectId $id): self
    {
        $this->currentId = $id;
        $this->simulatedProjects = $this->simulatedProjects->append($id);

        return $this;
    }

    public function thatRequires(Demand ...$demands): self
    {
        $this->simulatedDemands = $this->simulatedDemands->put($this->currentId->toString(), Demands::of(...$demands));

        return $this;
    }

    public function thatCanEarn(Decimal $earnings): self
    {
        $this->simulatedEarnings = $this->simulatedEarnings->put($this->currentId->toString(), $earnings);

        return $this;
    }

    /**
     * @return GenericList<SimulatedProject>
     */
    public function build(): GenericList
    {
        return $this->simulatedProjects->map(fn (ProjectId $id) => new SimulatedProject(
            $id,
            $this->simulatedEarnings->get($id->toString())->get(),
            $this->simulatedDemands->get($id->toString())->get()
        ));
    }
}
