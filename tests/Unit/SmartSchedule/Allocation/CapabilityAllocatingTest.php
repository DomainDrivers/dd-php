<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\AllocatedCapability;
use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Allocation\ResourceId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Set;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(AllocationFacade::class)]
final class CapabilityAllocatingTest extends KernelTestCase
{
    private AllocationFacade $allocationFacade;

    #[\Override]
    protected function setUp(): void
    {
        $this->allocationFacade = self::getContainer()->get(AllocationFacade::class);
    }

    #[Test]
    public function canAllocateCapabilityToProject(): void
    {
        // given
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $skillPhp = Capability::skill('php');
        $demand = new Demand($skillPhp, $oneDay);
        // and
        $allocatableResourceId = ResourceId::newOne();
        // and
        $projectId = ProjectAllocationsId::newOne();
        // and
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::of($demand));

        // when
        $result = $this->allocationFacade->allocateToProject($projectId, $allocatableResourceId, $skillPhp, $oneDay);

        // then
        self::assertTrue($result->isPresent());
        $summary = $this->allocationFacade->findAllProjectsAllocations();
        self::assertTrue($summary->projectAllocations->get($projectId->toString())->get()->all->equals(Set::of(AllocatedCapability::new($allocatableResourceId->id, $skillPhp, $oneDay))));
        self::assertTrue($summary->demands->get($projectId->toString())->get()->all->equals(GenericList::of($demand)));
    }

    #[Test]
    public function canReleaseCapabilityFromProject(): void
    {
        // given
        $oneDay = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        // and
        $allocatableResourceId = ResourceId::newOne();
        // and
        $projectId = ProjectAllocationsId::newOne();
        // and
        $this->allocationFacade->scheduleProjectAllocationDemands($projectId, Demands::none());
        // and
        $chosenCapability = Capability::skill('php');
        $allocatedId = $this->allocationFacade->allocateToProject($projectId, $allocatableResourceId, $chosenCapability, $oneDay);

        // when
        $result = $this->allocationFacade->releaseFromProject($projectId, $allocatedId->get(), $oneDay);

        // then
        self::assertTrue($result);
        $summary = $this->allocationFacade->findAllProjectsAllocations();
        self::assertTrue($summary->projectAllocations->get($projectId->toString())->get()->all->isEmpty());
    }
}
