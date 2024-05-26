<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use DomainDrivers\SmartSchedule\Allocation\Allocations;
use DomainDrivers\SmartSchedule\Allocation\CreateHourlyDemandsSummaryService;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocations;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreateHourlyDemandsSummaryService::class)]
final class CreateHourlyDemandsSummaryServiceTest extends TestCase
{
    private TimeSlot $jan;

    protected function setUp(): void
    {
        $this->jan = TimeSlot::createMonthlyTimeSlotAtUTC(2021, 1);
    }

    #[Test]
    public function createsMissingDemandsSummaryForAllGivenProjects(): void
    {
        // given
        $csharpProjectId = ProjectAllocationsId::newOne();
        $phpProjectId = ProjectAllocationsId::newOne();
        $charpDemands = Demands::of(new Demand(Capability::skill('c#'), $this->jan));
        $phpDemands = Demands::of(new Demand(Capability::skill('php'), $this->jan));
        $csharpProject = new ProjectAllocations($csharpProjectId, Allocations::none(), $charpDemands, $this->jan);
        $phpProject = new ProjectAllocations($phpProjectId, Allocations::none(), $phpDemands, $this->jan);

        // when
        $result = (new CreateHourlyDemandsSummaryService())->create(GenericList::of($csharpProject, $phpProject), $now = new \DateTimeImmutable());

        // then
        self::assertEquals($now, $result->occurredAt);
        self::assertTrue($result->missingDemands->equals(Map::fromArray([
            $csharpProjectId->toString() => $charpDemands,
            $phpProjectId->toString() => $phpDemands,
        ])));
    }

    #[Test]
    public function takesIntoAccountOnlyProjectsWithTimeSlot(): void
    {
        // given
        $withTimeSlotId = ProjectAllocationsId::newOne();
        $withoutTimeSlotId = ProjectAllocationsId::newOne();
        $withTimeSlotDemands = Demands::of(new Demand(Capability::skill('c#'), $this->jan));
        $withoutTimeSlotDemands = Demands::of(new Demand(Capability::skill('php'), $this->jan));
        $withTimeSlotProject = new ProjectAllocations($withTimeSlotId, Allocations::none(), $withTimeSlotDemands, $this->jan);
        $withoutTimeSlotProject = ProjectAllocations::withDemands($withoutTimeSlotId, $withoutTimeSlotDemands);

        // when
        $result = (new CreateHourlyDemandsSummaryService())->create(GenericList::of($withTimeSlotProject, $withoutTimeSlotProject), $now = new \DateTimeImmutable());

        // then
        self::assertEquals($now, $result->occurredAt);
        self::assertTrue($result->missingDemands->equals(Map::fromArray([
            $withTimeSlotId->toString() => $withTimeSlotDemands,
        ])));
    }
}
