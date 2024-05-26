<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use Munus\Collection\GenericList;
use Munus\Collection\Set;

interface ProjectRepository
{
    public function save(Project $project): void;

    public function getById(ProjectId $projectId): Project;

    /**
     * @param Set<ProjectId> $projectsIds
     *
     * @return GenericList<Project>
     */
    public function findAllById(Set $projectsIds): GenericList;

    /**
     * @return GenericList<Project>
     */
    public function findAll(): GenericList;
}
