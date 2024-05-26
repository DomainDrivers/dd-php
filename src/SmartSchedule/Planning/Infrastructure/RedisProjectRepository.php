<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use DomainDrivers\SmartSchedule\Planning\Project;
use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Planning\ProjectRepository;
use Munus\Collection\GenericList;
use Munus\Collection\Set;

final readonly class RedisProjectRepository implements ProjectRepository
{
    public function __construct(private \Redis $redis, private ProjectSerializer $serializer)
    {
    }

    #[\Override]
    public function save(Project $project): void
    {
        $this->redis->hSet('projects', $project->id()->toString(), $this->serializer->serialize($project));
    }

    #[\Override]
    public function getById(ProjectId $projectId): Project
    {
        $project = $this->redis->hGet('projects', $projectId->toString());
        if ($project === false) {
            throw new \RuntimeException(sprintf('Project %s not found', $projectId->toString()));
        }

        return $this->serializer->deserialize($project);
    }

    #[\Override]
    public function findAllById(Set $projectsIds): GenericList
    {
        /** @var string[] $projects */
        $projects = $this->redis->hMGet('projects', $projectsIds->map(fn (ProjectId $id) => $id->toString())->toArray());

        return GenericList::ofAll(array_map($this->serializer->deserialize(...), $projects));
    }

    #[\Override]
    public function findAll(): GenericList
    {
        return GenericList::ofAll(array_map($this->serializer->deserialize(...), $this->redis->hGetAll('projects')));
    }
}
