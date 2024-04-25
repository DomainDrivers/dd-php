<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning\Schedule;

use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStages;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ParallelStagesList;
use DomainDrivers\SmartSchedule\Planning\Parallelization\ResourceName;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\Schedule\Calendar;
use DomainDrivers\SmartSchedule\Planning\Schedule\Calendars;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Planning\Schedule\ScheduleBasedOnChosenResourcesAvailabilityCalculator;
use DomainDrivers\SmartSchedule\Planning\Schedule\ScheduleBasedOnReferenceStageCalculator;
use DomainDrivers\SmartSchedule\Planning\Schedule\ScheduleBasedOnStartDayCalculator;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Unit\SmartSchedule\Planning\Schedule\Assertions\ScheduleAssert;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Schedule::class)]
#[CoversClass(ScheduleBasedOnChosenResourcesAvailabilityCalculator::class)]
#[CoversClass(ScheduleBasedOnReferenceStageCalculator::class)]
#[CoversClass(ScheduleBasedOnStartDayCalculator::class)]
final class ScheduleCalculationTest extends TestCase
{
    private \DateTimeImmutable $jan1;
    private TimeSlot $jan10_20;
    private TimeSlot $jan1_1;
    private TimeSlot $jan3_10;
    private TimeSlot $jan1_20;
    private TimeSlot $jan11_21;
    private TimeSlot $jan1_4;
    private TimeSlot $jan4_14;
    private TimeSlot $jan14_16;
    private TimeSlot $jan1_5;
    private TimeSlot $dec29jan1;
    private TimeSlot $jan1_11;
    private TimeSlot $jan5_7;
    private TimeSlot $jan3_6;

    #[\Override]
    protected function setUp(): void
    {
        $this->jan1 = new \DateTimeImmutable('2020-01-01 00:00:00');
        $this->jan10_20 = new TimeSlot(new \DateTimeImmutable('2020-01-10 00:00:00'), new \DateTimeImmutable('2020-01-20 00:00:00'));
        $this->jan1_1 = new TimeSlot(new \DateTimeImmutable('2020-01-01 00:00:00'), new \DateTimeImmutable('2020-01-02 00:00:00'));
        $this->jan3_10 = new TimeSlot(new \DateTimeImmutable('2020-01-03 00:00:00'), new \DateTimeImmutable('2020-01-10 00:00:00'));
        $this->jan1_20 = new TimeSlot(new \DateTimeImmutable('2020-01-01 00:00:00'), new \DateTimeImmutable('2020-01-20 00:00:00'));
        $this->jan11_21 = new TimeSlot(new \DateTimeImmutable('2020-01-11 00:00:00'), new \DateTimeImmutable('2020-01-21 00:00:00'));
        $this->jan1_4 = new TimeSlot(new \DateTimeImmutable('2020-01-01 00:00:00'), new \DateTimeImmutable('2020-01-04 00:00:00'));
        $this->jan4_14 = new TimeSlot(new \DateTimeImmutable('2020-01-04 00:00:00'), new \DateTimeImmutable('2020-01-14 00:00:00'));
        $this->jan14_16 = new TimeSlot(new \DateTimeImmutable('2020-01-14 00:00:00'), new \DateTimeImmutable('2020-01-16 00:00:00'));
        $this->jan1_5 = new TimeSlot(new \DateTimeImmutable('2020-01-01 00:00:00'), new \DateTimeImmutable('2020-01-05 00:00:00'));
        $this->dec29jan1 = new TimeSlot(new \DateTimeImmutable('2019-12-29 00:00:00'), new \DateTimeImmutable('2020-01-01 00:00:00'));
        $this->jan1_11 = new TimeSlot(new \DateTimeImmutable('2020-01-01 00:00:00'), new \DateTimeImmutable('2020-01-11 00:00:00'));
        $this->jan5_7 = new TimeSlot(new \DateTimeImmutable('2020-01-05 00:00:00'), new \DateTimeImmutable('2020-01-07 00:00:00'));
        $this->jan3_6 = new TimeSlot(new \DateTimeImmutable('2020-01-03 00:00:00'), new \DateTimeImmutable('2020-01-06 00:00:00'));
    }

    public function canCalculateScheduleBasedOnTheStartDay(): void
    {
        // given
        $stage1 = Stage::of('Stage1')->ofDuration(Duration::ofDays(3));
        $stage2 = Stage::of('Stage2')->ofDuration(Duration::ofDays(10));
        $stage3 = Stage::of('Stage3')->ofDuration(Duration::ofDays(2));
        // and
        $parallelStages = ParallelStagesList::of(
            ParallelStages::of($stage1),
            ParallelStages::of($stage2),
            ParallelStages::of($stage3)
        );

        // when
        $schedule = Schedule::basedOnStartDay($this->jan1, $parallelStages);

        // then
        (new ScheduleAssert($schedule))
            ->hasStage('Stage1')->withSlot($this->jan1_4)
            ->and()
            ->hasStage('Stage2')->withSlot($this->jan4_14)
            ->and()
            ->hasStage('Stage3')->withSlot($this->jan14_16)
        ;
    }

    #[Test]
    public function scheduleCanAdjustToDatesOfOneReferenceStage(): void
    {
        // given
        $stage = Stage::of('S1')->ofDuration(Duration::ofDays(3));
        $anotherStage = Stage::of('S2')->ofDuration(Duration::ofDays(10));
        $yetAnotherStage = Stage::of('S3')->ofDuration(Duration::ofDays(2));
        $referenceStage = Stage::of('S4-Reference')->ofDuration(Duration::ofDays(4));
        // and
        $parallelStages = ParallelStagesList::of(
            ParallelStages::of($stage),
            ParallelStages::of($referenceStage, $anotherStage),
            ParallelStages::of($yetAnotherStage)
        );

        // when
        $schedule = Schedule::basedOnReferenceStageTimeSlot($referenceStage, $this->jan1_5, $parallelStages);

        // then
        (new ScheduleAssert($schedule))
            ->hasStage('S1')->withSlot($this->dec29jan1)->isBefore('S4-Reference')
            ->and()
            ->hasStage('S2')->withSlot($this->jan1_11)->startsTogetherWith('S4-Reference')
            ->and()
            ->hasStage('S3')->withSlot($this->jan5_7)->isAfter('S4-Reference')
            ->and()
            ->hasStage('S4-Reference')->withSlot($this->jan1_5)
        ;
    }

    #[Test]
    public function canAdjustScheduleToAvailabilityOfNeededResources(): void
    {
        // given
        $r1 = new ResourceName('r1');
        $r2 = new ResourceName('r2');
        $r3 = new ResourceName('r3');
        // and
        $stage1 = Stage::of('Stage1')->ofDuration(Duration::ofDays(3))->withChosenResourceCapabilities($r1);
        $stage2 = Stage::of('Stage2')->ofDuration(Duration::ofDays(10))->withChosenResourceCapabilities($r2, $r3);
        // and
        $cal1 = Calendar::withAvailableSlots($r1, $this->jan1_1, $this->jan3_10);
        $cal2 = Calendar::withAvailableSlots($r2, $this->jan1_20);
        $cal3 = Calendar::withAvailableSlots($r3, $this->jan11_21);

        // when
        $schedule = Schedule::basedOnChosenResourcesAvailability(Calendars::of($cal1, $cal2, $cal3), GenericList::of($stage1, $stage2));

        // then
        (new ScheduleAssert($schedule))
            ->hasStage('Stage1')->withSlot($this->jan3_6)
            ->and()
            ->hasStage('Stage2')->withSlot($this->jan10_20)
        ;
    }
}
