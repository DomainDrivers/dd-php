<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\ProjectAllocations;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsRepository;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Set;
use Munus\Collection\Stream\Collectors;
use Munus\Control\Option;

final class InMemoryProjectAllocationsRepository implements ProjectAllocationsRepository
{
    /**
     * @var Map<string, ProjectAllocations>
     */
    private Map $projects;

    public function __construct()
    {
        $this->projects = Map::empty();
    }

    #[\Override]
    public function save(ProjectAllocations $projectAllocations): void
    {
        $this->projects = $this->projects->put($projectAllocations->id()->toString(), $projectAllocations);
    }

    #[\Override]
    public function getById(ProjectAllocationsId $id): ProjectAllocations
    {
        return $this->projects->get($id->toString())->getOrElseThrow(new \RuntimeException(sprintf('Project allocations %s not found', $id->toString())));
    }

    #[\Override]
    public function findById(ProjectAllocationsId $id): Option
    {
        return $this->projects->get($id->toString());
    }

    #[\Override]
    public function findAll(): GenericList
    {
        return $this->projects->values()->collect(Collectors::toList());
    }

    #[\Override]
    public function findAllById(Set $ids): GenericList
    {
        return $this->projects->values()->filter(
            fn (ProjectAllocations $project) => $ids->contains($project->id())
        )->collect(Collectors::toList());
    }

    #[\Override]
    public function findAllContainingDate(\DateTimeImmutable $when): GenericList
    {
        return $this->projects->values()->filter(
            fn (ProjectAllocations $project) => $project->hasTimeSlot()
        )->collect(Collectors::toList());
    }
}
