<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Allocation\AllocatedCapability;
use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\PotentialTransfers;
use DomainDrivers\SmartSchedule\Allocation\PotentialTransfersService;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Allocation\ProjectsAllocationsSummary;
use DomainDrivers\SmartSchedule\Optimization\OptimizationFacade;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use DomainDrivers\SmartSchedule\Simulation\SimulationFacade;
use Munus\Collection\Map;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(AllocationFacade::class)]
final class PotentialTransferScenariosTest extends TestCase
{
    private TimeSlot $jan1;
    private TimeSlot $fifteenMinutesInJan;
    private Demands $demandForPhpJustFor15minInJan;
    private Demands $demandForPhpMidInJan;
    private Demands $demandsForPhpAndJavaScriptInJan;
    private ProjectAllocationsId $bankingSoftId;
    private ProjectAllocationsId $insuranceSoftId;
    private AllocatedCapability $staszekPhpMid;
    private PotentialTransfersService $potentialTransfers;

    #[\Override]
    protected function setUp(): void
    {
        $this->jan1 = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $this->fifteenMinutesInJan = new TimeSlot($this->jan1->from, $this->jan1->from->modify('+15 minutes'));
        $this->demandForPhpJustFor15minInJan = Demands::of(new Demand(Capability::skill('php-mid'), $this->fifteenMinutesInJan));
        $this->demandForPhpMidInJan = Demands::of(new Demand(Capability::skill('php-mid'), $this->jan1));
        $this->demandsForPhpAndJavaScriptInJan = Demands::of(
            new Demand(Capability::skill('php-mid'), $this->jan1),
            new Demand(Capability::skill('js-mid'), $this->jan1),
        );
        $this->bankingSoftId = ProjectAllocationsId::newOne();
        $this->insuranceSoftId = ProjectAllocationsId::newOne();
        $this->staszekPhpMid = AllocatedCapability::new(Uuid::v7(), Capability::skill('php-mid'), $this->jan1);
        $this->potentialTransfers = new PotentialTransfersService(new SimulationFacade(new OptimizationFacade()));
    }

    #[Test]
    public function simulatesMovingCapabilitiesToDifferentProject(): void
    {
        // given
        $bankingSoft = new Project($this->bankingSoftId, $this->demandForPhpMidInJan, new Decimal(9));
        $insuranceSoft = new Project($this->insuranceSoftId, $this->demandForPhpMidInJan, new Decimal(90));
        $bankingSoft->add($this->staszekPhpMid);
        $projects = $this->toPotentialTransfers($bankingSoft, $insuranceSoft);

        // when
        $result = $this->potentialTransfers->checkPotentialTransfer($projects, $this->bankingSoftId, $this->insuranceSoftId, $this->staszekPhpMid, $this->jan1);

        // then
        self::assertTrue($result->equals(new Decimal(81)));
    }

    #[Test]
    public function simulatesMovingCapabilitiesToDifferentProjectJustForAWhile(): void
    {
        // given
        $bankingSoft = new Project($this->bankingSoftId, $this->demandForPhpMidInJan, new Decimal(9));
        $insuranceSoft = new Project($this->insuranceSoftId, $this->demandForPhpJustFor15minInJan, new Decimal(99));
        $bankingSoft->add($this->staszekPhpMid);
        $projects = $this->toPotentialTransfers($bankingSoft, $insuranceSoft);

        // when
        $result = $this->potentialTransfers->checkPotentialTransfer($projects, $this->bankingSoftId, $this->insuranceSoftId, $this->staszekPhpMid, $this->jan1);

        // then
        self::assertTrue($result->equals(new Decimal(90)));
    }

    #[Test]
    public function theMoveGivesZeroProfitWhenThereAreStillMissingDemands(): void
    {
        // given
        $bankingSoft = new Project($this->bankingSoftId, $this->demandForPhpMidInJan, new Decimal(9));
        $insuranceSoft = new Project($this->insuranceSoftId, $this->demandsForPhpAndJavaScriptInJan, new Decimal(99));
        $bankingSoft->add($this->staszekPhpMid);
        $projects = $this->toPotentialTransfers($bankingSoft, $insuranceSoft);

        // when
        $result = $this->potentialTransfers->checkPotentialTransfer($projects, $this->bankingSoftId, $this->insuranceSoftId, $this->staszekPhpMid, $this->jan1);

        // then
        self::assertTrue($result->equals(new Decimal(-9)));
    }

    private function toPotentialTransfers(Project ...$projects): PotentialTransfers
    {
        $allocations = Map::empty();
        $demands = Map::empty();
        $earnings = Map::empty();
        foreach ($projects as $project) {
            $allocations = $allocations->put($project->id->toString(), $project->allocations);
            $demands = $demands->put($project->id->toString(), $project->demands);
            $earnings = $earnings->put($project->id->toString(), $project->earnings);
        }

        return new PotentialTransfers(new ProjectsAllocationsSummary(Map::empty(), $allocations, $demands), $earnings);
    }
}
