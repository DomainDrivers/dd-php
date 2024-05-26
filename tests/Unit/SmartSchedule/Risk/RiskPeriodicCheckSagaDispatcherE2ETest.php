<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Risk;

use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\NotSatisfiedDemands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationScheduled;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Availability\Owner;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Availability\ResourceTakenOver;
use DomainDrivers\SmartSchedule\Risk\RiskPeriodicCheckSagaDispatcher;
use DomainDrivers\SmartSchedule\Risk\RiskPushNotification;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Phpunit\WithConsecutiveHelper;
use Munus\Collection\Map;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[CoversClass(RiskPeriodicCheckSagaDispatcher::class)]
final class RiskPeriodicCheckSagaDispatcherE2ETest extends KernelTestCase
{
    use WithConsecutiveHelper;
    use InteractsWithMessenger;

    private TimeSlot $oneDayLong;
    private TimeSlot $projectDates;

    private RiskPeriodicCheckSagaDispatcher $riskPeriodicCheckSagaDispatcher;
    private RiskPushNotification&MockObject $riskPushNotification;
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->oneDayLong = TimeSlot::createDailyTimeSlotAtUTC(2022, 1, 1);
        $this->projectDates = new TimeSlot(new \DateTimeImmutable(), (new \DateTimeImmutable())->modify('+20 days'));

        $this->riskPushNotification = $this->createMock(RiskPushNotification::class);
        self::getContainer()->set(RiskPushNotification::class, $this->riskPushNotification);

        $this->clock = new MockClock();
        self::getContainer()->set(ClockInterface::class, $this->clock);

        $this->riskPeriodicCheckSagaDispatcher = self::getContainer()->get(RiskPeriodicCheckSagaDispatcher::class);
    }

    #[Test]
    public function informsAboutDemandSatisfied(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $php = Capability::skill('php-mid');
        $phpOneDay = new Demand($php, $this->oneDayLong);
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleNotSatisfiedDemands(NotSatisfiedDemands::forOneProject($projectId, Demands::of($phpOneDay), $this->clock->now()));

        // then
        $this->riskPushNotification->expects(self::atLeastOnce())->method('notifyDemandsSatisfied')->with($projectId);

        // when
        $this->riskPeriodicCheckSagaDispatcher->handleNotSatisfiedDemands(NotSatisfiedDemands::allSatisfied($projectId, $this->clock->now()));
    }

    #[Test]
    public function informsAboutDemandSatisfiedForAllProjects(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $projectId2 = ProjectAllocationsId::newOne();

        // then
        $this->riskPushNotification->expects(self::exactly(2))->method('notifyDemandsSatisfied')->with(...self::withConsecutive([$projectId], [$projectId2]));

        // when
        $this->riskPeriodicCheckSagaDispatcher->handleNotSatisfiedDemands(NotSatisfiedDemands::new(Map::fromArray([
            $projectId->toString() => Demands::none(),
            $projectId2->toString() => Demands::none(),
        ]), $this->clock->now()));
    }

    #[Test]
    public function informsAboutPotentialRiskWhenResourceTakenOver(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $php = Capability::skill('php-mid');
        $phpOneDay = new Demand($php, $this->oneDayLong);
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleNotSatisfiedDemands(NotSatisfiedDemands::forOneProject($projectId, Demands::of($phpOneDay), $this->clock->now()));
        // and
        $this->riskPeriodicCheckSagaDispatcher->handleNotSatisfiedDemands(NotSatisfiedDemands::allSatisfied($projectId, $this->clock->now()));
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

    private function itIsDaysBeforeDeadline(int $days): void
    {
        $this->clock->modify(sprintf('-%s days', $days));
        $this->clock->modify('+20 days');
    }
}
