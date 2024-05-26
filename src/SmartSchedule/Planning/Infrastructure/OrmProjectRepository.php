<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use DomainDrivers\SmartSchedule\Planning\Project;
use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Planning\ProjectRepository;
use Munus\Collection\GenericList;
use Munus\Collection\Set;

final readonly class OrmProjectRepository implements ProjectRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[\Override]
    public function save(Project $project): void
    {
        $this->entityManager->persist($project);
        $this->entityManager->flush();
    }

    #[\Override]
    public function getById(ProjectId $projectId): Project
    {
        return $this->entityManager->find(Project::class, $projectId) ?? throw new \RuntimeException(sprintf('Project %s not found', $projectId->toString()));
    }

    #[\Override]
    public function findAllById(Set $projectsIds): GenericList
    {
        return GenericList::ofAll($this->entityManager->getRepository(Project::class)->findBy([
            'id' => $projectsIds->toArray(),
        ]));
    }

    #[\Override]
    public function findAll(): GenericList
    {
        return GenericList::ofAll($this->entityManager->getRepository(Project::class)->findAll());
    }
}
