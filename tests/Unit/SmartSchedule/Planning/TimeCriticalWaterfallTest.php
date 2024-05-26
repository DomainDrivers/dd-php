<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Unit\SmartSchedule\Planning\Schedule\Assertions\ScheduleAssert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(PlanningFacade::class)]
final class TimeCriticalWaterfallTest extends KernelTestCase
{
    private PlanningFacade $projectFacade;
    private TimeSlot $jan_1_5;
    private TimeSlot $jan_1_3;
    private TimeSlot $jan_1_4;

    #[\Override]
    protected function setUp(): void
    {
        $this->projectFacade = self::getContainer()->get(PlanningFacade::class);
        $this->jan_1_5 = TimeSlot::with('2020-01-01 00:00:00', '2020-01-05 00:00:00');
        $this->jan_1_3 = TimeSlot::with('2020-01-01 00:00:00', '2020-01-03 00:00:00');
        $this->jan_1_4 = TimeSlot::with('2020-01-01 00:00:00', '2020-01-04 00:00:00');
    }

    #[Test]
    public function timeCriticalWaterfallProjectProcess(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('waterfall');

        // and
        $stageBeforeCritical = Stage::of('stage1')->ofDuration(Duration::ofDays(2));
        $criticalStage = Stage::of('stage2')->ofDuration($this->jan_1_5->duration());
        $stageAfterCritical = Stage::of('stage3')->ofDuration(Duration::ofDays(3));
        $this->projectFacade->defineProjectStages($projectId, $stageBeforeCritical, $criticalStage, $stageAfterCritical);

        // when
        $this->projectFacade->planCriticalStage($projectId, $criticalStage, $this->jan_1_5);

        // then
        (new ScheduleAssert($this->projectFacade->load($projectId)->schedule))
            ->hasStage('stage1')->withSlot($this->jan_1_3)
            ->and()
            ->hasStage('stage2')->withSlot($this->jan_1_5)
            ->and()
            ->hasStage('stage3')->withSlot($this->jan_1_4);
    }
}
