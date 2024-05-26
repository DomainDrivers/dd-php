<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation;

use Munus\Collection\GenericList;
use Munus\Collection\Set;
use Munus\Control\Option;

interface ProjectAllocationsRepository
{
    public function save(ProjectAllocations $projectAllocations): void;

    public function getById(ProjectAllocationsId $id): ProjectAllocations;

    /**
     * @return Option<ProjectAllocations>
     */
    public function findById(ProjectAllocationsId $id): Option;

    /**
     * @return GenericList<ProjectAllocations>
     */
    public function findAll(): GenericList;

    /**
     * @param Set<ProjectAllocationsId> $ids
     *
     * @return GenericList<ProjectAllocations>
     */
    public function findAllById(Set $ids): GenericList;

    /**
     * @return GenericList<ProjectAllocations>
     */
    public function findAllContainingDate(\DateTimeImmutable $when): GenericList;
}
