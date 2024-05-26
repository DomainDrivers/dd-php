<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Planning\Project;
use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Planning\ProjectRepository;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Set;
use Munus\Collection\Stream\Collectors;

final class InMemoryProjectRepository implements ProjectRepository
{
    /**
     * @var Map<string, Project>
     */
    private Map $projects;

    public function __construct()
    {
        $this->projects = Map::empty();
    }

    #[\Override]
    public function save(Project $project): void
    {
        $this->projects = $this->projects->put($project->id()->toString(), $project);
    }

    #[\Override]
    public function getById(ProjectId $projectId): Project
    {
        return $this->projects->get($projectId->toString())->getOrElseThrow(new \RuntimeException(sprintf('Project %s not found', $projectId->toString())));
    }

    #[\Override]
    public function findAllById(Set $projectsIds): GenericList
    {
        return $this->projects->values()->filter(
            fn (Project $project) => $projectsIds->contains($project->id())
        )->collect(Collectors::toList());
    }

    #[\Override]
    public function findAll(): GenericList
    {
        return $this->projects->values()->collect(Collectors::toList());
    }
}
