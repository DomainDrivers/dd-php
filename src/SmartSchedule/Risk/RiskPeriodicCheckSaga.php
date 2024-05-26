<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Version;
use DomainDrivers\SmartSchedule\Allocation\CapabilitiesAllocated;
use DomainDrivers\SmartSchedule\Allocation\CapabilityReleased;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Earnings;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\EarningsRecalculated;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationScheduled;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsDemandsScheduled;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Availability\ResourceTakenOver;

#[Entity]
#[Table('project_risk_sagas')]
class RiskPeriodicCheckSaga
{
    private const int RISK_THRESHOLD_VALUE = 1000;
    private const int UPCOMING_DEADLINE_AVAILABILITY_SEARCH = 30;
    private const int UPCOMING_DEADLINE_REPLACEMENT_SUGGESTION = 15;

    #[Id]
    #[Column(type: 'risk_periodic_check_saga_id')]
    private RiskPeriodicCheckSagaId $id;

    #[Column(type: 'project_allocations_id')]
    private ProjectAllocationsId $projectId;

    #[Column(type: 'allocation_demands', options: ['jsonb' => true])]
    private Demands $missingDemands;

    #[Column(type: 'earnings')]
    private Earnings $earnings;

    #[Version]
    #[Column(type: 'bigint')]
    private int $version;

    #[Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deadline = null;

    public function __construct(ProjectAllocationsId $projectId, ?Demands $missingDemands = null, ?Earnings $earnings = null)
    {
        $this->id = RiskPeriodicCheckSagaId::newOne();
        $this->projectId = $projectId;
        $this->missingDemands = $missingDemands ?? Demands::none();
        $this->earnings = $earnings ?? Earnings::of(0);
    }

    public function areDemandsSatisfied(): bool
    {
        return false;
    }

    public function handleEarningsRecalculated(EarningsRecalculated $event): RiskPeriodicCheckSagaStep
    {
    }

    public function handleProjectAllocationsDemandsScheduled(ProjectAllocationsDemandsScheduled $event): RiskPeriodicCheckSagaStep
    {
    }

    public function handleProjectAllocationScheduled(ProjectAllocationScheduled $event): RiskPeriodicCheckSagaStep
    {
    }

    public function handleResourceTakenOver(ResourceTakenOver $event): RiskPeriodicCheckSagaStep
    {
    }

    public function handleCapabilityReleased(CapabilityReleased $event): RiskPeriodicCheckSagaStep
    {
    }

    public function handleCapabilitiesAllocated(CapabilitiesAllocated $event): RiskPeriodicCheckSagaStep
    {
    }

    public function handleWeeklyCheck(\DateTimeImmutable $when): RiskPeriodicCheckSagaStep
    {
    }

    public function missingDemands(): Demands
    {
        return $this->missingDemands;
    }

    public function id(): RiskPeriodicCheckSagaId
    {
        return $this->id;
    }

    public function projectId(): ProjectAllocationsId
    {
        return $this->projectId;
    }

    public function earnings(): Earnings
    {
        return $this->earnings;
    }

    public function deadline(): ?\DateTimeImmutable
    {
        return $this->deadline;
    }
}
