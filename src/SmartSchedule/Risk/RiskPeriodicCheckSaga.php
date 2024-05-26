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
        return $this->missingDemands->all->isEmpty();
    }

    public function handleEarningsRecalculated(EarningsRecalculated $event): RiskPeriodicCheckSagaStep
    {
        $this->earnings = $event->earnings;

        return RiskPeriodicCheckSagaStep::DO_NOTHING;
    }

    public function handleProjectAllocationsDemandsScheduled(ProjectAllocationsDemandsScheduled $event): RiskPeriodicCheckSagaStep
    {
        $this->missingDemands = $event->missingDemands;
        if ($this->areDemandsSatisfied()) {
            return RiskPeriodicCheckSagaStep::NOTIFY_ABOUT_DEMANDS_SATISFIED;
        }

        return RiskPeriodicCheckSagaStep::DO_NOTHING;
    }

    public function handleProjectAllocationScheduled(ProjectAllocationScheduled $event): RiskPeriodicCheckSagaStep
    {
        $this->deadline = $event->fromTo->to;

        return RiskPeriodicCheckSagaStep::DO_NOTHING;
    }

    public function handleResourceTakenOver(ResourceTakenOver $event): RiskPeriodicCheckSagaStep
    {
        if ($this->deadline !== null && $event->occurredAt > $this->deadline) {
            return RiskPeriodicCheckSagaStep::DO_NOTHING;
        }

        return RiskPeriodicCheckSagaStep::NOTIFY_ABOUT_POSSIBLE_RISK;
    }

    public function handleCapabilityReleased(CapabilityReleased $event): RiskPeriodicCheckSagaStep
    {
        $this->missingDemands = $event->missingDemands;

        return RiskPeriodicCheckSagaStep::DO_NOTHING;
    }

    public function handleCapabilitiesAllocated(CapabilitiesAllocated $event): RiskPeriodicCheckSagaStep
    {
        $this->missingDemands = $event->missingDemands;
        if ($this->areDemandsSatisfied()) {
            return RiskPeriodicCheckSagaStep::NOTIFY_ABOUT_DEMANDS_SATISFIED;
        }

        return RiskPeriodicCheckSagaStep::DO_NOTHING;
    }

    public function handleWeeklyCheck(\DateTimeImmutable $when): RiskPeriodicCheckSagaStep
    {
        if ($this->deadline === null || $when > $this->deadline) {
            return RiskPeriodicCheckSagaStep::DO_NOTHING;
        }

        if ($this->areDemandsSatisfied()) {
            return RiskPeriodicCheckSagaStep::DO_NOTHING;
        }

        $daysToDeadline = ($this->deadline->getTimestamp() - $when->getTimestamp()) / 86400;
        if ($daysToDeadline > self::UPCOMING_DEADLINE_AVAILABILITY_SEARCH) {
            return RiskPeriodicCheckSagaStep::DO_NOTHING;
        }
        if ($daysToDeadline > self::UPCOMING_DEADLINE_REPLACEMENT_SUGGESTION) {
            return RiskPeriodicCheckSagaStep::FIND_AVAILABLE;
        }
        if ($this->earnings->greaterThan(Earnings::of(self::RISK_THRESHOLD_VALUE))) {
            return RiskPeriodicCheckSagaStep::SUGGEST_REPLACEMENT;
        }

        return RiskPeriodicCheckSagaStep::DO_NOTHING;
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
