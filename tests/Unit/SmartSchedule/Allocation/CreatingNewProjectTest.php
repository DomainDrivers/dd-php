<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling\CapabilityFinder;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationScheduled;
use DomainDrivers\SmartSchedule\Availability\AvailabilityFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\NativeClock;

#[CoversClass(AllocationFacade::class)]
final class CreatingNewProjectTest extends TestCase
{
    private AllocationFacade $allocationFacade;
    private EventsPublisher&MockObject $eventsPublisher;
    private TimeSlot $jan;
    private TimeSlot $feb;

    #[\Override]
    protected function setUp(): void
    {
        $this->allocationFacade = new AllocationFacade(
            new InMemoryProjectAllocationsRepository(),
            $this->createMock(AvailabilityFacade::class),
            $this->createMock(CapabilityFinder::class),
            $this->eventsPublisher = $this->createMock(EventsPublisher::class),
            new NativeClock()
        );
        $this->jan = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $this->feb = TimeSlot::createDailyTimeSlotAtUTC(2021, 2, 1);
    }

    #[Test]
    public function canCreateNewProject(): void
    {
        // given
        $demand = new Demand(Capability::skill('php'), $this->jan);

        // then
        $this->eventsPublisher->expects(self::once())->method('publish')->with(self::callback(fn (ProjectAllocationScheduled $event): bool => $event->fromTo->equals($this->jan)));

        // when
        $demands = Demands::of($demand);
        $newProject = $this->allocationFacade->createAllocation($this->jan, $demands);

        // then
        $projectSummary = $this->allocationFacade->findAllProjectsAllocationsBy(Set::of($newProject));
        self::assertTrue($projectSummary->demands->get($newProject->toString())->get()->all->equals($demands->all));
        self::assertTrue($projectSummary->timeSlots->get($newProject->toString())->get()->equals($this->jan));
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
