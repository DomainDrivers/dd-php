<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Risk;

use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\AllocatableCapabilityId;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Earnings;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\EarningsRecalculated;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationScheduled;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceTakenOver;
use DomainDrivers\SmartSchedule\Risk\RiskPeriodicCheckSaga;
use DomainDrivers\SmartSchedule\Risk\RiskPeriodicCheckSagaStep;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

#[CoversClass(RiskPeriodicCheckSaga::class)]
final class RiskPeriodicCheckSagaTest extends TestCase
{
    private MockClock $clock;
    private Capability $php;
    private TimeSlot $oneDay;
    private Demands $singleDemand;
    private Demands $manyDemands;
    private TimeSlot $projectDates;
    private ProjectAllocationsId $projectId;
    private AllocatableCapabilityId $capabilityId;

    protected function setUp(): void
    {
        $this->clock = new MockClock();
        $this->php = Capability::skill('php');
        $this->oneDay = TimeSlot::createDailyTimeSlotAtUTC(2022, 1, 1);
        $this->singleDemand = Demands::of(new Demand($this->php, $this->oneDay));
        $this->manyDemands = Demands::of(new Demand($this->php, $this->oneDay));
        $this->projectDates = TimeSlot::with('2021-01-01 00:00:00.00', '2021-01-05 00:00:00.00');
        $this->projectId = ProjectAllocationsId::newOne();
        $this->capabilityId = AllocatableCapabilityId::newOne();
    }

    #[Test]
    public function updatesInitialDemandsOnSagaCreation(): void
    {
        // when
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);

        // then
        self::assertEquals($this->singleDemand, $saga->getMissingDemands());
    }

    #[Test]
    public function updatesDeadlineOnDeadlineSet(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);
        // and
        $saga->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($this->projectId, $this->projectDates, $this->clock->now()));

        // then
        self::assertEquals($this->projectDates->to, $saga->deadline());
    }

    #[Test]
    public function updateMissingDemands(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);

        // when
        $nextStep = $saga->missingDemands($this->manyDemands);

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);
        self::assertEquals($this->manyDemands, $saga->getMissingDemands());
    }

    #[Test]
    public function noNewStepsOnWhenMissingDemands(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->manyDemands);

        // when
        $nextStep = $saga->missingDemands($this->manyDemands);

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);
    }

    #[Test]
    public function updatedEarningsOnEarningsRecalculated(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);

        // when
        $nextStep = $saga->handleEarningsRecalculated(EarningsRecalculated::new($this->projectId, Earnings::of(1000), $this->clock->now()));
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);

        // then
        self::assertTrue(Earnings::of(1000)->equals($saga->earnings()));

        // when
        $nextStep = $saga->handleEarningsRecalculated(EarningsRecalculated::new($this->projectId, Earnings::of(900), $this->clock->now()));

        // then
        self::assertTrue(Earnings::of(900)->equals($saga->earnings()));
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);
    }

    #[Test]
    public function informsAboutDemandsSatisfiedWhenNoMissingDemands(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->manyDemands);
        // and
        $saga->handleEarningsRecalculated(EarningsRecalculated::new($this->projectId, Earnings::of(1000), $this->clock->now()));
        // when
        $stillMissing = $saga->missingDemands($this->singleDemand);
        $zeroDemands = $saga->missingDemands(Demands::none());

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $stillMissing);
        self::assertEquals(RiskPeriodicCheckSagaStep::NOTIFY_ABOUT_DEMANDS_SATISFIED, $zeroDemands);
    }

    #[Test]
    public function doNothingOnResourceTakenOverWhenAfterDeadline(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->manyDemands);
        // and
        $saga->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($this->projectId, $this->projectDates, $this->clock->now()));

        // when
        $afterDeadline = $this->projectDates->to->modify('+100 hours');
        $nextStep = $saga->handleResourceTakenOver(ResourceTakenOver::new($this->capabilityId->toAvailabilityResourceId(), Set::of(Owner::of($this->projectId->id)), $this->oneDay, $afterDeadline));

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);
    }

    #[Test]
    public function notifyAboutRiskOnResourceTakenOverWhenBeforeDeadline(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->manyDemands);
        // and
        $saga->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($this->projectId, $this->projectDates, $this->clock->now()));

        // when
        $beforeDeadline = $this->projectDates->to->modify('-100 hours');
        $nextStep = $saga->handleResourceTakenOver(ResourceTakenOver::new($this->capabilityId->toAvailabilityResourceId(), Set::of(Owner::of($this->projectId->id)), $this->oneDay, $beforeDeadline));

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::NOTIFY_ABOUT_POSSIBLE_RISK, $nextStep);
    }

    #[Test]
    public function noNextStepOnCapabilityReleased(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);

        // when
        $nextStep = $saga->missingDemands($this->singleDemand);

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);
    }

    #[Test]
    public function weeklyCheckShouldResultInNothingWhenAllDemandsSatisfied(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);
        // and
        $saga->handleEarningsRecalculated(EarningsRecalculated::new($this->projectId, Earnings::of(1000), $this->clock->now()));
        // and
        $saga->missingDemands(Demands::none());
        // and
        $saga->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($this->projectId, $this->projectDates, $this->clock->now()));

        // when
        $wayBeforeDeadline = $this->projectDates->to->modify('-1 day');
        $nextStep = $saga->handleWeeklyCheck($wayBeforeDeadline);

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);
    }

    #[Test]
    public function weeklyCheckShouldResultInNothingWhenAfterDeadline(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);
        // and
        $saga->handleEarningsRecalculated(EarningsRecalculated::new($this->projectId, Earnings::of(1000), $this->clock->now()));
        // and
        $saga->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($this->projectId, $this->projectDates, $this->clock->now()));

        // when
        $wayAfterDeadline = $this->projectDates->to->modify('+300 days');
        $nextStep = $saga->handleWeeklyCheck($wayAfterDeadline);

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);
    }

    #[Test]
    public function weeklyCheckDoesNothingWhenNoDeadline(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);

        $nextStep = $saga->handleWeeklyCheck($this->clock->now());

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);
    }

    #[Test]
    public function weeklyCheckShouldResultInNothingWhenNotCloseToDeadlineAndDemandsNotSatisfied(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);
        // and
        $saga->handleEarningsRecalculated(EarningsRecalculated::new($this->projectId, Earnings::of(1000), $this->clock->now()));
        // and
        $saga->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($this->projectId, $this->projectDates, $this->clock->now()));

        // when
        $wayBeforeDeadline = $this->projectDates->to->modify('-300 days');
        $nextStep = $saga->handleWeeklyCheck($wayBeforeDeadline);

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::DO_NOTHING, $nextStep);
    }

    #[Test]
    public function weeklyCheckShouldResultInFindAvailableWhenCloseToDeadlineAndDemandsNotSatisfied(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);
        // and
        $saga->handleEarningsRecalculated(EarningsRecalculated::new($this->projectId, Earnings::of(1000), $this->clock->now()));
        // and
        $saga->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($this->projectId, $this->projectDates, $this->clock->now()));

        // when
        $closeToDeadline = $this->projectDates->to->modify('-20 days');
        $nextStep = $saga->handleWeeklyCheck($closeToDeadline);

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::FIND_AVAILABLE, $nextStep);
    }

    #[Test]
    public function weeklyCheckShouldResultInReplacementSuggestingWhenHighValueProjectReallyCloseToDeadlineAndDemandsNotSatisfied(): void
    {
        // given
        $saga = new RiskPeriodicCheckSaga($this->projectId, $this->singleDemand);
        // and
        $saga->handleEarningsRecalculated(EarningsRecalculated::new($this->projectId, Earnings::of(10000), $this->clock->now()));
        // and
        $saga->handleProjectAllocationScheduled(ProjectAllocationScheduled::new($this->projectId, $this->projectDates, $this->clock->now()));

        // when
        $reallyCloseToDeadline = $this->projectDates->to->modify('-2 days');
        $nextStep = $saga->handleWeeklyCheck($reallyCloseToDeadline);

        // then
        self::assertEquals(RiskPeriodicCheckSagaStep::SUGGEST_REPLACEMENT, $nextStep);
    }
}
