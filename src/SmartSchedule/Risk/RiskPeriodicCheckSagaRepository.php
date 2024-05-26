<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk;

use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use Munus\Collection\GenericList;

interface RiskPeriodicCheckSagaRepository
{
    public function save(RiskPeriodicCheckSaga $periodicCheckSaga): void;

    public function findByProjectId(ProjectAllocationsId $projectId): ?RiskPeriodicCheckSaga;

    /**
     * @param GenericList<ProjectAllocationsId> $interested
     *
     * @return GenericList<RiskPeriodicCheckSaga>
     */
    public function findByProjectIdIn(GenericList $interested): GenericList;

    /**
     * @return GenericList<RiskPeriodicCheckSaga>
     */
    public function findAll(): GenericList;

    public function findByProjectIdOrCreate(ProjectAllocationsId $projectId): RiskPeriodicCheckSaga;

    /**
     * @param GenericList<ProjectAllocationsId> $interested
     *
     * @return GenericList<RiskPeriodicCheckSaga>
     */
    public function findByProjectIdInOrElseCreate(GenericList $interested): GenericList;
}
