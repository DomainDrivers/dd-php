<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Unit\SmartSchedule\Planning\Schedule\Assertions\ScheduleAssert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(PlanningFacade::class)]
final class SpecializedWaterfallTest extends KernelTestCase
{
    private PlanningFacade $projectFacade;
    private TimeSlot $jan_1_2;
    private TimeSlot $jan_1_4;
    private TimeSlot $jan_1_6;
    private TimeSlot $jan_4_8;

    #[\Override]
    protected function setUp(): void
    {
        $this->projectFacade = self::getContainer()->get(PlanningFacade::class);
        $this->jan_1_2 = TimeSlot::with('2020-01-01 00:00:00', '2020-01-02 00:00:00');
        $this->jan_1_4 = TimeSlot::with('2020-01-01 00:00:00', '2020-01-04 00:00:00');
        $this->jan_1_6 = TimeSlot::with('2020-01-01 00:00:00', '2020-01-06 00:00:00');
        $this->jan_4_8 = TimeSlot::with('2020-01-04 00:00:00', '2020-01-08 00:00:00');
    }

    #[Test]
    public function specializedWaterfallProjectProcess(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('waterfall');

        // and
        $criticalStageDuration = Duration::ofDays(5);
        $stage1Duration = Duration::ofDays(1);
        $stageBeforeCritical = Stage::of('stage1')->ofDuration($stage1Duration);
        $criticalStage = Stage::of('stage2')->ofDuration($criticalStageDuration);
        $stageAfterCritical = Stage::of('stage3')->ofDuration(Duration::ofDays(3));
        $this->projectFacade->defineProjectStages($projectId, $stageBeforeCritical, $criticalStage, $stageAfterCritical);

        // and
        $criticalResourceName = ResourceId::newOne();
        $criticalCapabilityAvailability = $this->resourceAvailableForCapabilityInPeriod($criticalResourceName, Capability::skill('JAVA'), $this->jan_1_6);

        // when
        $this->projectFacade->planCriticalStageWithResource($projectId, $criticalStage, $criticalResourceName, $this->jan_4_8);

        // then
        $this->verifyResourcesNotAvailable($projectId, $criticalCapabilityAvailability, $this->jan_4_8);

        // when
        $this->projectFacade->planCriticalStageWithResource($projectId, $criticalStage, $criticalResourceName, $this->jan_1_6);

        // then
        $this->assertResourcesAvailable($projectId, $criticalCapabilityAvailability);
        // and
        (new ScheduleAssert($this->projectFacade->load($projectId)->schedule))
            ->hasStage('stage1')->withSlot($this->jan_1_2)
            ->and()
            ->hasStage('stage2')->withSlot($this->jan_1_6)
            ->and()
            ->hasStage('stage3')->withSlot($this->jan_1_4);
    }

    private function resourceAvailableForCapabilityInPeriod(ResourceId $resource, Capability $capability, TimeSlot $slot): ResourceId
    {
        return ResourceId::newOne();
    }

    private function verifyResourcesNotAvailable(ProjectId $projectId, ResourceId $resource, TimeSlot $requestedButNotAvailable): void
    {
    }

    private function assertResourcesAvailable(ProjectId $projectId, ResourceId $resource): void
    {
    }
}
