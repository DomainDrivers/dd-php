<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\Demand;
use DomainDrivers\SmartSchedule\Planning\Demands;
use DomainDrivers\SmartSchedule\Planning\DemandsPerStage;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Unit\SmartSchedule\Planning\Schedule\Assertions\ScheduleAssert;
use Munus\Collection\Map;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(PlanningFacade::class)]
final class StandardWaterfallTest extends KernelTestCase
{
    private PlanningFacade $projectFacade;
    private \DateTimeImmutable $jan_1;
    private ResourceId $resource_1;
    private ResourceId $resource_2;
    private ResourceId $resource_4;
    private TimeSlot $jan_1_2;
    private TimeSlot $jan_2_5;
    private TimeSlot $jan_2_12;

    #[\Override]
    protected function setUp(): void
    {
        self::getContainer()->get('snc_redis.default')->flushAll();
        $this->projectFacade = self::getContainer()->get(PlanningFacade::class);
        $this->jan_1 = new \DateTimeImmutable('2020-01-01 00:00:00.00');
        $this->resource_1 = ResourceId::newOne();
        $this->resource_2 = ResourceId::newOne();
        $this->resource_4 = ResourceId::newOne();
        $this->jan_1_2 = TimeSlot::with('2020-01-01 00:00:00', '2020-01-02 00:00:00');
        $this->jan_2_5 = TimeSlot::with('2020-01-02 00:00:00', '2020-01-05 00:00:00');
        $this->jan_2_12 = TimeSlot::with('2020-01-02 00:00:00', '2020-01-12 00:00:00');
    }

    #[Test]
    public function waterfallProjectProcess(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('waterfall');

        // when
        $this->projectFacade->defineProjectStages($projectId, Stage::of('stage1'), Stage::of('stage2'), Stage::of('stage3'));

        // then
        $projectCard = $this->projectFacade->load($projectId);
        self::assertSame('stage1, stage2, stage3', $projectCard->parallelStagesList->print());

        // when
        $demandsPerStage = new DemandsPerStage(Map::fromArray(['stage1' => Demands::of(Demand::forSkill('java'))]));
        $this->projectFacade->defineDemandsPerStage($projectId, $demandsPerStage);

        // then
        $this->verifyRiskDuringPlanning($projectId);

        // when
        $this->projectFacade->defineProjectStages(
            $projectId,
            Stage::of('stage1')->withChosenResourceCapabilities($this->resource_1),
            Stage::of('stage2')->withChosenResourceCapabilities($this->resource_2, $this->resource_1),
            Stage::of('stage3')->withChosenResourceCapabilities($this->resource_4)
        );

        // then
        $projectCard = $this->projectFacade->load($projectId);
        self::assertContains($projectCard->parallelStagesList->print(), ['stage1 | stage2, stage3', 'stage2, stage3 | stage1']);

        // when
        $this->projectFacade->defineProjectStages(
            $projectId,
            Stage::of('stage1')->ofDuration(Duration::ofDays(1))->withChosenResourceCapabilities($this->resource_1),
            Stage::of('stage2')->ofDuration(Duration::ofDays(3))->withChosenResourceCapabilities($this->resource_2, $this->resource_1),
            Stage::of('stage3')->ofDuration(Duration::ofDays(10))->withChosenResourceCapabilities($this->resource_4)
        );

        // and
        $this->projectFacade->defineStartDate($projectId, $this->jan_1);

        // then
        (new ScheduleAssert($this->projectFacade->load($projectId)->schedule))
            ->hasStage('stage1')->withSlot($this->jan_1_2)
            ->and()
            ->hasStage('stage2')->withSlot($this->jan_2_5)
            ->and()
            ->hasStage('stage3')->withSlot($this->jan_2_12);
    }

    private function verifyRiskDuringPlanning(ProjectId $projectId): void
    {
    }
}
