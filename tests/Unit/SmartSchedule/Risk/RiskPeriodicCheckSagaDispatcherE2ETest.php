<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Risk;

use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\CapabilitiesAllocated;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilitiesSummary;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilitySummary;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashFlowFacade;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Cost;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Earnings;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\EarningsRecalculated;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Income;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationScheduled;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsDemandsScheduled;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Availability\ResourceTakenOver;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeFacade;
use DomainDrivers\SmartSchedule\Resource\Employee\Seniority;
use DomainDrivers\SmartSchedule\Risk\RiskPeriodicCheckSagaDispatcher;
use DomainDrivers\SmartSchedule\Risk\RiskPushNotification;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Map;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[CoversClass(RiskPeriodicCheckSagaDispatcher::class)]
final class RiskPeriodicCheckSagaDispatcherE2ETest extends KernelTestCase
{
    use InteractsWithMessenger;

    private TimeSlot $oneDayLong;
    private TimeSlot $projectDates;

    private EmployeeFacade $employeeFacade;
    private AllocationFacade $allocationFacade;
    private RiskPeriodicCheckSagaDispatcher $riskPeriodicCheckSagaDispatcher;
    private RiskPushNotification&MockObject $riskPushNotification;
    private CashFlowFacade $cashFlowFacade;
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->oneDayLong = TimeSlot::createDailyTimeSlotAtUTC(2022, 1, 1);
        $this->projectDates = new TimeSlot(new \DateTimeImmutable(), (new \DateTimeImmutable())->modify('+20 days'));

        $this->riskPushNotification = $this->createMock(RiskPushNotification::class);
        self::getContainer()->set(RiskPushNotification::class, $this->riskPushNotification);

        $this->clock = new MockClock();
        self::getContainer()->set(ClockInterface::class, $this->clock);

        $this->allocationFacade = self::getContainer()->get(AllocationFacade::class);
        $this->riskPeriodicCheckSagaDispatcher = self::getContainer()->get(RiskPeriodicCheckSagaDispatcher::class);
        $this->employeeFacade = self::getContainer()->get(EmployeeFacade::class);
        $this->cashFlowFacade = self::getContainer()->get(CashFlowFacade::class);
    }

    #[Test]
    public function informsAboutDemandSatisfied(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $php = Capability::skill('php-mid');
        $phpOneDay = new Demand($php, $this->oneDayLong);
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationsDemandsScheduled(ProjectAllocationsDemandsScheduled::new($projectId, Demands::of($phpOneDay), $this->clock->now()));

        // then
        $this->riskPushNotification->expects(self::atLeastOnce())->method('notifyDemandsSatisfied')->with($projectId);

        // when
        $this->riskPeriodicCheckSagaDispatcher->handleCapabilitiesAllocated(CapabilitiesAllocated::new(Uuid::v7(), $projectId, Demands::none(), $this->clock->now()));
    }

    #[Test]
    public function informsAboutPotentialRiskWhenResourceTakenOver(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $php = Capability::skill('php-mid');
        $phpOneDay = new Demand($php, $this->oneDayLong);
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationsDemandsScheduled(ProjectAllocationsDemandsScheduled::new($projectId, Demands::of($phpOneDay), $this->clock->now()));
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleCapabilitiesAllocated(CapabilitiesAllocated::new(Uuid::v7(), $projectId, Demands::none(), $this->clock->now()));
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($projectId, $this->projectDates, $this->clock->now()));

        // then
        $this->riskPushNotification->expects(self::atLeastOnce())->method('notifyAboutPossibleRisk')->with($projectId);

        // when
        $this->itIsDaysBeforeDeadline(100);
        $this->riskPeriodicCheckSagaDispatcher->handleResourceTakenOver(ResourceTakenOver::new(ResourceId::newOne(), Set::of(Owner::of($projectId->id)), $this->oneDayLong, $this->clock->now()));
    }

    #[Test]
    public function doesNothingWhenResourceTakenOverFromFromUnknownProject(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();

        // then
        $this->riskPushNotification->expects(self::never())->method(self::anything());

        // when
        $this->riskPeriodicCheckSagaDispatcher->handleResourceTakenOver(ResourceTakenOver::new(ResourceId::newOne(), Set::of(Owner::of($projectId->id)), $this->oneDayLong, $this->clock->now()));
    }

    #[Test]
    public function weeklyCheckDoesNothingWhenNotCloseToDeadlineAndDemandsNotSatisfied(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $php = Capability::skill('php-mid');
        $phpOneDay = new Demand($php, $this->oneDayLong);
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationsDemandsScheduled(ProjectAllocationsDemandsScheduled::new($projectId, Demands::of($phpOneDay), $this->clock->now()));
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($projectId, $this->projectDates, $this->clock->now()));

        // then
        $this->riskPushNotification->expects(self::never())->method(self::anything());

        // when
        $this->itIsDaysBeforeDeadline(100);
        $this->riskPeriodicCheckSagaDispatcher->handleWeeklyCheck();
    }

    #[Test]
    public function weeklyCheckDoesNothingWhenCloseToDeadlineAndDemandsSatisfied(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $php = Capability::skill('php-mid');
        $phpOneDay = new Demand($php, $this->oneDayLong);
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationsDemandsScheduled(ProjectAllocationsDemandsScheduled::new($projectId, Demands::of($phpOneDay), $this->clock->now()));
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleEarningsRecalculated(EarningsRecalculated::new($projectId, Earnings::of(10), $this->clock->now()));
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleCapabilitiesAllocated(CapabilitiesAllocated::new(Uuid::v7(), $projectId, Demands::none(), $this->clock->now()));
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($projectId, $this->projectDates, $this->clock->now()));

        // then
        $this->riskPushNotification->expects(self::never())->method(self::anything());

        // when
        $this->itIsDaysBeforeDeadline(100);
        $this->riskPeriodicCheckSagaDispatcher->handleWeeklyCheck();
    }

    #[Test]
    public function findReplacementsWhenDeadlineClose(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $php = Capability::skill('php-mid');
        $phpOneDay = new Demand($php, $this->oneDayLong);
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationsDemandsScheduled(ProjectAllocationsDemandsScheduled::new($projectId, Demands::of($phpOneDay), $this->clock->now()));
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleEarningsRecalculated(EarningsRecalculated::new($projectId, Earnings::of(10), $this->clock->now()));
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($projectId, $this->projectDates, $this->clock->now()));
        // and
        $employee = $this->thereIsEmployeeWithSkills(Set::of($php), $this->oneDayLong);

        // then
        $this->riskPushNotification->expects(self::once())->method('notifyAboutAvailability')->with(
            $projectId,
            self::callback(function (Map $available) use ($phpOneDay, $employee): bool {
                /** @var Map<string, AllocatableCapabilitiesSummary> $available */
                return $available->get((string) $phpOneDay)->get()->all->anyMatch(
                    fn (AllocatableCapabilitySummary $ac) => $ac->id->equals($employee)
                );
            })
        );

        // when
        $this->itIsDaysBeforeDeadline(20);
        $this->riskPeriodicCheckSagaDispatcher->handleWeeklyCheck();
    }

    #[Test]
    public function suggestResourcesFromDifferentProjects(): void
    {
        // given
        $highValueProject = ProjectAllocationsId::newOne();
        $lowValueProject = ProjectAllocationsId::newOne();
        // and
        $php = Capability::skill('php-mid-super-unique');
        $phpOneDay = new Demand($php, $this->oneDayLong);
        // and
        $this->allocationFacade->scheduleProjectAllocationDemands($highValueProject, Demands::of($phpOneDay));
        $this->cashFlowFacade->addIncomeAndCost($highValueProject, Income::of(10000), Cost::of(10));
        $this->allocationFacade->scheduleProjectAllocationDemands($lowValueProject, Demands::of($phpOneDay));
        $this->cashFlowFacade->addIncomeAndCost($lowValueProject, Income::of(100), Cost::of(10));
        $this->transport('event')->process(4);
        // and
        $employee = $this->thereIsEmployeeWithSkills(Set::of($php), $this->oneDayLong);
        $this->allocationFacade->allocateToProject($lowValueProject, $employee, $this->oneDayLong);
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($highValueProject, $this->projectDates, $this->clock->now()));

        // then
        $this->riskPushNotification->expects(self::atLeastOnce())->method('notifyProfitableRelocationFound')->with($highValueProject, $employee);

        // when
        $this->allocationFacade->editProjectDates($highValueProject, $this->projectDates);
        $this->allocationFacade->editProjectDates($lowValueProject, $this->projectDates);
        $this->itIsDaysBeforeDeadline(1);
        $this->riskPeriodicCheckSagaDispatcher->handleWeeklyCheck();
    }

    /**
     * @param Set<Capability> $skills
     */
    private function thereIsEmployeeWithSkills(Set $skills, TimeSlot $inSlot): AllocatableCapabilityId
    {
        $staszek = $this->employeeFacade->addEmployee('Staszek', 'Staszkowski', Seniority::MID, $skills, Capability::permissions());
        $allocatableCapabilityIds = $this->employeeFacade->scheduleCapabilities($staszek, $inSlot);
        \assert($allocatableCapabilityIds->length() === 1);

        return $allocatableCapabilityIds->get();
    }

    private function itIsDaysBeforeDeadline(int $days): void
    {
        $this->clock->modify(sprintf('-%s days', $days));
        $this->clock->modify('+20 days');
    }
}
