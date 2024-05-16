<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Planning\ProjectCard;
use DomainDrivers\SmartSchedule\Planning\ProjectId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\Tests\Unit\SmartSchedule\Planning\Schedule\Assertions\ScheduleAssert;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(PlanningFacade::class)]
final class RDTest extends KernelTestCase
{
    private PlanningFacade $projectFacade;
    private AvailabilityFacade $availabilityFacade;
    private TimeSlot $january;
    private TimeSlot $february;
    private TimeSlot $march;
    private TimeSlot $q1;
    private TimeSlot $jan_1_4;
    private TimeSlot $feb_2_16;
    private TimeSlot $mar_1_6;

    #[\Override]
    protected function setUp(): void
    {
        $this->projectFacade = self::getContainer()->get(PlanningFacade::class);
        $this->availabilityFacade = self::getContainer()->get(AvailabilityFacade::class);
        $this->january = TimeSlot::with('2020-01-01 00:00:00', '2020-01-31 00:00:00');
        $this->february = TimeSlot::with('2020-02-01 00:00:00', '2020-02-28 00:00:00');
        $this->march = TimeSlot::with('2020-03-01 00:00:00', '2020-03-31 00:00:00');
        $this->q1 = TimeSlot::with('2020-01-01 00:00:00', '2020-03-31 00:00:00');
        $this->jan_1_4 = TimeSlot::with('2020-01-01 00:00:00', '2020-01-04 00:00:00');
        $this->feb_2_16 = TimeSlot::with('2020-02-01 00:00:00', '2020-02-16 00:00:00');
        $this->mar_1_6 = TimeSlot::with('2020-03-01 00:00:00', '2020-03-06 00:00:00');
    }

    #[Test]
    public function researchAndDevelopmentProjectProcess(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('waterfall');

        // and
        $r1 = ResourceId::newOne();
        $javaAvailableInJanuary = $this->resourceAvailableForCapabilityInPeriod($r1, Capability::skill('JAVA'), $this->january);
        $r2 = ResourceId::newOne();
        $phpAvailableInFebruary = $this->resourceAvailableForCapabilityInPeriod($r2, Capability::skill('PHP'), $this->february);
        $r3 = ResourceId::newOne();
        $csharpAvailableInMarch = $this->resourceAvailableForCapabilityInPeriod($r3, Capability::skill('CSHARP'), $this->march);
        $allResources = Set::of($r1, $r2, $r3);

        // when
        $this->projectFacade->defineResourcesWithinDates($projectId, $allResources, $this->january);

        // then
        $this->verifyThatResourcesAreMissing($projectId, Set::of($phpAvailableInFebruary, $csharpAvailableInMarch));

        // when
        $this->projectFacade->defineResourcesWithinDates($projectId, $allResources, $this->february);

        // then
        $this->verifyThatResourcesAreMissing($projectId, Set::of($javaAvailableInJanuary, $csharpAvailableInMarch));

        // when
        $this->projectFacade->defineResourcesWithinDates($projectId, $allResources, $this->q1);

        // then
        $this->verifyThatNoResourcesAreMissing($projectId);

        // when
        $this->projectFacade->adjustStagesToResourceAvailability(
            $projectId,
            $this->q1,
            Stage::of('Stage1')->ofDuration(Duration::ofDays(3))->withChosenResourceCapabilities($r1),
            Stage::of('Stage2')->ofDuration(Duration::ofDays(15))->withChosenResourceCapabilities($r2),
            Stage::of('Stage3')->ofDuration(Duration::ofDays(5))->withChosenResourceCapabilities($r3),
        );

        // then
        $loaded = $this->projectFacade->load($projectId);
        (new ScheduleAssert($loaded->schedule))
            ->hasStage('Stage1')->withSlot($this->jan_1_4)
            ->and()
            ->hasStage('Stage2')->withSlot($this->feb_2_16)
            ->and()
            ->hasStage('Stage3')->withSlot($this->mar_1_6);
        $this->projectIsNotParallelized($loaded);
    }

    private function resourceAvailableForCapabilityInPeriod(ResourceId $resource, Capability $capability, TimeSlot $slot): ResourceId
    {
        $this->availabilityFacade->createResourceSlots($resource, $slot);

        return ResourceId::newOne();
    }

    /**
     * @param Set<ResourceId> $missingResources
     */
    private function verifyThatResourcesAreMissing(ProjectId $projectId, Set $missingResources): void
    {
    }

    private function verifyThatNoResourcesAreMissing(ProjectId $projectId): void
    {
    }

    private function projectIsNotParallelized(ProjectCard $loaded): void
    {
        self::assertTrue($loaded->parallelStagesList->all()->equals(GenericList::empty()));
    }
}
