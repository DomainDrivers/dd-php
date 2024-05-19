<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Planning;

use DomainDrivers\SmartSchedule\Availability\ResourceId;
use DomainDrivers\SmartSchedule\Planning\CapabilitiesDemanded;
use DomainDrivers\SmartSchedule\Planning\ChosenResources;
use DomainDrivers\SmartSchedule\Planning\Demand;
use DomainDrivers\SmartSchedule\Planning\Demands;
use DomainDrivers\SmartSchedule\Planning\DemandsPerStage;
use DomainDrivers\SmartSchedule\Planning\Parallelization\Stage;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Planning\ProjectCard;
use DomainDrivers\SmartSchedule\Planning\Schedule\Schedule;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\Duration;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Map;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[CoversClass(PlanningFacade::class)]
final class PlanningFacadeTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private PlanningFacade $projectFacade;

    #[\Override]
    protected function setUp(): void
    {
        $this->projectFacade = self::getContainer()->get(PlanningFacade::class);
    }

    #[Test]
    public function canCreateProjectAndLoadProjectCard(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('project', Stage::of('Stage1'));

        // when
        $loaded = $this->projectFacade->load($projectId);

        // then
        self::assertTrue($projectId->id->equals($loaded->projectId->id));
        self::assertSame('project', $loaded->name);
        self::assertSame('Stage1', $loaded->parallelStagesList->print());
    }

    #[Test]
    public function canLoadMultipleProjects(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('project', Stage::of('Stage1'));
        $projectId2 = $this->projectFacade->addNewProjectWith('project2', Stage::of('Stage2'));
        $projectsIds = Set::of($projectId, $projectId2);

        // when
        $loaded = $this->projectFacade->loadAll($projectsIds);

        // then
        self::assertTrue($loaded->map(fn (ProjectCard $projectCard) => $projectCard->projectId)->containsAll($projectsIds));
    }

    #[Test]
    public function canCreateAndSaveMoreComplexParallelization(): void
    {
        // given
        $stage1 = Stage::of('Stage1');
        $stage2 = Stage::of('Stage2');
        $stage3 = Stage::of('Stage3');
        $stage2 = $stage2->dependsOn($stage1);
        $stage3 = $stage3->dependsOn($stage2);

        // and
        $projectId = $this->projectFacade->addNewProjectWith('project', $stage1, $stage2, $stage3);

        // when
        $loaded = $this->projectFacade->load($projectId);

        // then
        self::assertSame('Stage1 | Stage2 | Stage3', $loaded->parallelStagesList->print());
    }

    #[Test]
    public function canPlanDemands(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('project', Stage::of('Stage1'));

        // when
        $demandForPhp = Demands::of(Demand::forSkill('php'));
        $this->projectFacade->addDemands($projectId, $demandForPhp);

        // then
        $loaded = $this->projectFacade->load($projectId);
        self::assertTrue($demandForPhp->all->containsAll($loaded->demands->all));
    }

    #[Test]
    public function canPlanNewDemands(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('project', Stage::of('Stage1'));

        // when
        $demandForPhp = Demand::forSkill('php');
        $demandForPython = Demand::forSkill('php');
        $this->projectFacade->addDemands($projectId, Demands::of($demandForPhp));
        $this->projectFacade->addDemands($projectId, Demands::of($demandForPython));

        // then
        $loaded = $this->projectFacade->load($projectId);
        self::assertTrue(Demands::of($demandForPhp, $demandForPython)->all->containsAll($loaded->demands->all));
    }

    #[Test]
    public function canPlanDemandsPerStage(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('project', Stage::of('Stage1'));

        // when
        $php = Demands::of(Demand::forSkill('php'));
        $demandsPerStage = new DemandsPerStage(Map::fromArray(['Stage1' => $php]));
        $this->projectFacade->defineDemandsPerStage($projectId, $demandsPerStage);

        // then
        $loaded = $this->projectFacade->load($projectId);
        self::assertTrue($php->all->containsAll($loaded->demands->all));
        self::assertTrue($demandsPerStage->demands->equals($loaded->demandsPerStage->demands));
    }

    #[Test]
    public function canPlanNeededResourcesInTime(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('project', Stage::of('Stage1'));

        // when
        $neededResources = Set::of(ResourceId::newOne());
        $firstHalfOfTheYear = new TimeSlot(new \DateTimeImmutable('2021-01-01 00:00:00.00'), new \DateTimeImmutable('2021-06-01 00:00:00.00'));
        $this->projectFacade->defineResourcesWithinDates($projectId, $neededResources, $firstHalfOfTheYear);

        // then
        $loaded = $this->projectFacade->load($projectId);
        self::assertEquals(new ChosenResources($neededResources, $firstHalfOfTheYear), $loaded->chosenResources);
    }

    #[Test]
    public function canRedefineStages(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('project', Stage::of('Stage1'));

        // when
        $this->projectFacade->defineProjectStages($projectId, Stage::of('Stage2'));

        // then
        $loaded = $this->projectFacade->load($projectId);
        self::assertSame('Stage2', $loaded->parallelStagesList->print());
    }

    #[Test]
    public function canCalculateScheduleAfterPassingPossibleStart(): void
    {
        // given
        $stage1 = Stage::of('Stage1')->ofDuration(Duration::ofDays(2));
        $stage2 = Stage::of('Stage2')->ofDuration(Duration::ofDays(5));
        $stage3 = Stage::of('Stage3')->ofDuration(Duration::ofDays(7));

        // and
        $projectId = $this->projectFacade->addNewProjectWith('project', $stage1, $stage2, $stage3);

        // when
        $this->projectFacade->defineStartDate($projectId, new \DateTimeImmutable('2021-01-01 00:00:00.00'));

        // then
        $loaded = $this->projectFacade->load($projectId);
        self::assertTrue($loaded->schedule->dates->equals(Map::fromArray([
            'Stage1' => TimeSlot::with('2021-01-01 00:00:00.00', '2021-01-03 00:00:00.00'),
            'Stage2' => TimeSlot::with('2021-01-01 00:00:00.00', '2021-01-06 00:00:00.00'),
            'Stage3' => TimeSlot::with('2021-01-01 00:00:00.00', '2021-01-08 00:00:00.00'),
        ])));
    }

    #[Test]
    public function canManuallyAddSchedule(): void
    {
        // given
        $stage1 = Stage::of('Stage1')->ofDuration(Duration::ofDays(2));
        $stage2 = Stage::of('Stage2')->ofDuration(Duration::ofDays(5));
        $stage3 = Stage::of('Stage3')->ofDuration(Duration::ofDays(7));

        // and
        $projectId = $this->projectFacade->addNewProjectWith('project', $stage1, $stage2, $stage3);

        // when
        $manualSchedule = [
            'Stage1' => TimeSlot::with('2021-01-01 00:00:00.00', '2021-01-03 00:00:00.00'),
            'Stage2' => TimeSlot::with('2021-01-03 00:00:00.00', '2021-01-08 00:00:00.00'),
            'Stage3' => TimeSlot::with('2021-01-08 00:00:00.00', '2021-01-15 00:00:00.00'),
        ];
        $this->projectFacade->defineManualSchedule($projectId, new Schedule(Map::fromArray($manualSchedule)));

        // then
        $loaded = $this->projectFacade->load($projectId);
        self::assertTrue($loaded->schedule->dates->equals(Map::fromArray($manualSchedule)));
    }

    #[Test]
    public function capabilitiesDemandedEventIsEmittedAfterAddingDemands(): void
    {
        // given
        $projectId = $this->projectFacade->addNewProjectWith('project', Stage::of('Stage1'));

        // when
        $demandForPhp = Demands::of(Demand::forSkill('php'));
        $this->projectFacade->addDemands($projectId, $demandForPhp);

        // then
        $this->transport()->queue()
            ->assertCount(1)
            ->first(fn (CapabilitiesDemanded $event): bool => $event->projectId->id->equals($projectId->id) && $event->demands->all->equals($demandForPhp->all)
            )
        ;
    }
}
