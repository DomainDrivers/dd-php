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
     * @var Map<string, \Closure(): Decimal>
     */
    private Map $values;

    public function __construct()
    {
        $this->simulatedProjects = GenericList::empty();
        $this->simulatedDemands = Map::empty();
        $this->values = Map::empty();
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
        $this->values = $this->values->put($this->currentId->toString(), fn () => $earnings);

        return $this;
    }

    public function thatCanGenerateReputationLoss(int $factor): self
    {
        $this->values = $this->values->put($this->currentId->toString(), fn () => new Decimal($factor));

        return $this;
    }

    /**
     * @return GenericList<SimulatedProject>
     */
    public function build(): GenericList
    {
        return $this->simulatedProjects->map(fn (ProjectId $id) => new SimulatedProject(
            $id,
            $this->values->get($id->toString())->get(),
            $this->simulatedDemands->get($id->toString())->get()
        ));
    }
}
