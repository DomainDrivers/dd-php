<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning\Parallelization;

use DomainDrivers\SmartSchedule\Planning\Parallelization\DurationCalculator;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DurationCalculator::class)]
final class DurationCalculatorTest extends TestCase
{
    private DurationCalculator $durationCalculator;

    #[\Override]
    protected function setUp(): void
    {
        $this->durationCalculator = new DurationCalculator();
    }

    #[Test]
    public function longestStageIsTakenIntoAccount(): void
    {
        // given
        $stage1 = Stage::of('Stage1')->ofDuration(Duration::zero());
        $stage2 = Stage::of('Stage2')->ofDuration(Duration::ofDays(3));
        $stage3 = Stage::of('Stage3')->ofDuration(Duration::ofDays(2));
        $stage4 = Stage::of('Stage4')->ofDuration(Duration::ofDays(5));

        // when
        $duration = $this->durationCalculator->apply(GenericList::of($stage1, $stage2, $stage3, $stage4));

        // then
        self::assertTrue($duration->equals(Duration::ofDays(5)));
    }

    #[Test]
    public function sumIsTakenIntoAccountWhenNothingIsParallel(): void
    {
        // given
        $stage1 = Stage::of('Stage1')->ofDuration(Duration::ofHours(10));
        $stage2 = Stage::of('Stage2')->ofDuration(Duration::ofHours(24));
        $stage3 = Stage::of('Stage3')->ofDuration(Duration::ofDays(2));
        $stage4 = Stage::of('Stage4')->ofDuration(Duration::ofDays(1));

        $stage4 = $stage4->dependsOn($stage3);
        $stage3 = $stage3->dependsOn($stage2);
        $stage2 = $stage2->dependsOn($stage1);

        // when
        $duration = $this->durationCalculator->apply(GenericList::of($stage1, $stage2, $stage3, $stage4));

        // then
        self::assertTrue($duration->equals(Duration::ofHours(106)));
    }
}
