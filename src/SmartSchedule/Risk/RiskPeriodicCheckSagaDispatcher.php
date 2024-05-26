<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Risk;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilitiesSummary;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilitySummary;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityFinder;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\EarningsRecalculated;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\NotSatisfiedDemands;
use DomainDrivers\SmartSchedule\Allocation\PotentialTransfersService;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationScheduled;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceTakenOver;
use Munus\Collection\Map;
use Munus\Collection\Stream\Collectors;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

final readonly class RiskPeriodicCheckSagaDispatcher
{
    public function __construct(
        private RiskPeriodicCheckSagaRepository $riskSagaRepository,
        private PotentialTransfersService $potentialTransfersService,
        private CapabilityFinder $capabilityFinder,
        private RiskPushNotification $riskPushNotification,
        private ClockInterface $clock
    ) {
    }

    #[AsMessageHandler(bus: 'event')]
    // remember about transactions spanning saga and potential external system
    public function handleEarningsRecalculated(EarningsRecalculated $event): void
    {
        $found = $this->riskSagaRepository->findByProjectId($event->projectId);
        if ($found === null) {
            $found = new RiskPeriodicCheckSaga($event->projectId, earnings: $event->earnings);
        }
        $nextStep = $found->handleEarningsRecalculated($event);
        $this->riskSagaRepository->save($found);
        $this->perform($nextStep, $found);
    }

    #[AsMessageHandler(bus: 'event')]
    // remember about transactions spanning saga and potential external system
    public function handleProjectAllocationScheduled(ProjectAllocationScheduled $event): void
    {
        $found = $this->riskSagaRepository->findByProjectIdOrCreate($event->projectId);
        $nextStep = $found->handleProjectAllocationScheduled($event);
        $this->riskSagaRepository->save($found);
        $this->perform($nextStep, $found);
    }

    #[AsMessageHandler(bus: 'event')]
    // remember about transactions spanning saga and potential external system
    public function handleNotSatisfiedDemands(NotSatisfiedDemands $event): void
    {
        $sagas = $this->riskSagaRepository->findByProjectIdInOrElseCreate($event->missingDemands->keys()->toStream()->map(fn (string $key) => ProjectAllocationsId::fromString($key))->collect(Collectors::toList()));
        foreach ($sagas as $saga) {
            $missingDemands = $event->missingDemands->get($saga->projectId()->toString())->get();
            $nextStep = $saga->missingDemands($missingDemands);
            $this->riskSagaRepository->save($saga);
            $this->perform($nextStep, $saga);
        }
    }

    #[AsMessageHandler(bus: 'event')]
    // remember about transactions spanning saga and potential external system
    public function handleResourceTakenOver(ResourceTakenOver $event): void
    {
        $interested = $event->previousOwners->toStream()->map(fn (Owner $o) => new ProjectAllocationsId($o->getId()))->collect(Collectors::toList());
        $this->riskSagaRepository->findByProjectIdIn($interested)->forEach(
            fn (RiskPeriodicCheckSaga $saga) => $this->handle($saga, $event)
        );
    }

    private function handle(RiskPeriodicCheckSaga $saga, ResourceTakenOver $event): void
    {
        $nextStep = $saga->handleResourceTakenOver($event);
        $this->riskSagaRepository->save($saga);
        $this->perform($nextStep, $saga);
    }

    #[AsCronTask('@weekly')]
    public function handleWeeklyCheck(): void
    {
        $this->riskSagaRepository->findAll()->forEach(function (RiskPeriodicCheckSaga $saga): void {
            $nextStep = $saga->handleWeeklyCheck($this->clock->now());
            $this->riskSagaRepository->save($saga);
            $this->perform($nextStep, $saga);
        });
    }

    private function perform(RiskPeriodicCheckSagaStep $nextStep, RiskPeriodicCheckSaga $saga): void
    {
        match ($nextStep) {
            RiskPeriodicCheckSagaStep::NOTIFY_ABOUT_DEMANDS_SATISFIED => $this->riskPushNotification->notifyDemandsSatisfied($saga->projectId()),
            RiskPeriodicCheckSagaStep::FIND_AVAILABLE => $this->handleFindAvailableFor($saga),
            RiskPeriodicCheckSagaStep::DO_NOTHING => null,
            RiskPeriodicCheckSagaStep::SUGGEST_REPLACEMENT => $this->handleSimulateRelocation($saga),
            RiskPeriodicCheckSagaStep::NOTIFY_ABOUT_POSSIBLE_RISK => $this->riskPushNotification->notifyAboutPossibleRisk($saga->projectId())
        };
    }

    private function handleFindAvailableFor(RiskPeriodicCheckSaga $saga): void
    {
        $replacements = $this->findAvailableReplacementsFor($saga->getMissingDemands());
        if (!$replacements->values()->flatMap(fn (AllocatableCapabilitiesSummary $ac) => $ac->all->toStream()->collect(Collectors::toList()))->isEmpty()) {
            $this->riskPushNotification->notifyAboutAvailability($saga->projectId(), $replacements);
        }
    }

    private function handleSimulateRelocation(RiskPeriodicCheckSaga $saga): void
    {
        $this->findPossibleReplacements($saga->getMissingDemands())->values()->forEach(function (AllocatableCapabilitiesSummary $replacements) use ($saga) {
            $replacements->all->forEach(function (AllocatableCapabilitySummary $replacement) use ($saga) {
                $profitAfterMovingCapabilities = $this->potentialTransfersService->profitAfterMovingCapabilities($saga->projectId(), $replacement, $replacement->timeSlot);
                if ($profitAfterMovingCapabilities->isPositive()) {
                    $this->riskPushNotification->notifyProfitableRelocationFound($saga->projectId(), $replacement->id);
                }
            });
        });
    }

    /**
     * @return Map<string, AllocatableCapabilitiesSummary>
     */
    private function findAvailableReplacementsFor(Demands $demands): Map
    {
        return $demands->all->toStream()->collect(Collectors::toMap(
            fn (Demand $d) => (string) $d,
            fn (Demand $d) => $this->capabilityFinder->findAvailableCapabilities($d->capability, $d->slot)
        ));
    }

    /**
     * @return Map<string, AllocatableCapabilitiesSummary>
     */
    private function findPossibleReplacements(Demands $demands): Map
    {
        return $demands->all->toStream()->collect(Collectors::toMap(
            fn (Demand $d) => (string) $d,
            fn (Demand $d) => $this->capabilityFinder->findCapabilities($d->capability, $d->slot)
        ));
    }
}
