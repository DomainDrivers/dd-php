<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationScheduled;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[CoversClass(AllocationFacade::class)]
final class CreatingNewProjectTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private AllocationFacade $allocationFacade;
    private TimeSlot $jan;
    private TimeSlot $feb;

    #[\Override]
    protected function setUp(): void
    {
        $this->allocationFacade = self::getContainer()->get(AllocationFacade::class);
        $this->jan = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $this->feb = TimeSlot::createDailyTimeSlotAtUTC(2021, 2, 1);
    }

    #[Test]
    public function canCreateNewProject(): void
    {
        // given
        $demand = new Demand(Capability::skill('php'), $this->jan);

        // when
        $demands = Demands::of($demand);
        $newProject = $this->allocationFacade->createAllocation($this->jan, $demands);

        // then
        $projectSummary = $this->allocationFacade->findAllProjectsAllocationsBy(Set::of($newProject));
        self::assertTrue($projectSummary->demands->get($newProject->toString())->get()->all->equals($demands->all));
        self::assertTrue($projectSummary->timeSlots->get($newProject->toString())->get()->equals($this->jan));

        $this->transport('event')->queue()
            ->assertCount(1)
            ->first(fn (ProjectAllocationScheduled $event): bool => $event->projectId->id->equals($newProject->id) && $event->fromTo->equals($this->jan)
            )
        ;
    }

    #[Test]
    public function canRedefineProjectDeadline(): void
    {
        // given
        $demand = new Demand(Capability::skill('php'), $this->jan);

        // and
        $demands = Demands::of($demand);
        $newProject = $this->allocationFacade->createAllocation($this->jan, $demands);

        // when
        $this->allocationFacade->editProjectDates($newProject, $this->feb);

        // then
        $projectSummary = $this->allocationFacade->findAllProjectsAllocationsBy(Set::of($newProject));
        self::assertTrue($projectSummary->timeSlots->get($newProject->toString())->get()->equals($this->feb));
    }
}
