<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Allocation\AllocatedCapability;
use DomainDrivers\SmartSchedule\Allocation\AllocationFacade;
use DomainDrivers\SmartSchedule\Allocation\Demand;
use DomainDrivers\SmartSchedule\Allocation\Demands;
use DomainDrivers\SmartSchedule\Allocation\Project;
use DomainDrivers\SmartSchedule\Allocation\Projects;
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
    private Uuid $bankingSoftId;
    private Uuid $insuranceSoftId;
    private AllocatedCapability $staszekPhpMid;
    private AllocationFacade $simulationFacade;

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
        $this->bankingSoftId = Uuid::v7();
        $this->insuranceSoftId = Uuid::v7();
        $this->staszekPhpMid = AllocatedCapability::new(Uuid::v7(), Capability::skill('php-mid'), $this->jan1);
        $this->simulationFacade = new AllocationFacade(new SimulationFacade(new OptimizationFacade()));
    }

    #[Test]
    public function simulatesMovingCapabilitiesToDifferentProject(): void
    {
        // given
        $bankingSoft = new Project($this->demandForPhpMidInJan, new Decimal(9));
        $insuranceSoft = new Project($this->demandForPhpMidInJan, new Decimal(90));
        $projects = new Projects(Map::fromArray([
            $this->bankingSoftId->toRfc4122() => $bankingSoft,
            $this->insuranceSoftId->toRfc4122() => $insuranceSoft,
        ]));

        // and
        $bankingSoft->add($this->staszekPhpMid);

        // when
        $result = $this->simulationFacade->checkPotentialTransfer($projects, $this->bankingSoftId, $this->insuranceSoftId, $this->staszekPhpMid, $this->jan1);

        // then
        self::assertTrue($result->equals(new Decimal(81)));
    }

    #[Test]
    public function simulatesMovingCapabilitiesToDifferentProjectJustForAWhile(): void
    {
        // given
        $bankingSoft = new Project($this->demandForPhpMidInJan, new Decimal(9));
        $insuranceSoft = new Project($this->demandForPhpJustFor15minInJan, new Decimal(99));
        $projects = new Projects(Map::fromArray([
            $this->bankingSoftId->toRfc4122() => $bankingSoft,
            $this->insuranceSoftId->toRfc4122() => $insuranceSoft,
        ]));

        // and
        $bankingSoft->add($this->staszekPhpMid);

        // when
        $result = $this->simulationFacade->checkPotentialTransfer($projects, $this->bankingSoftId, $this->insuranceSoftId, $this->staszekPhpMid, $this->jan1);

        // then
        self::assertTrue($result->equals(new Decimal(90)));
    }

    #[Test]
    public function theMoveGivesZeroProfitWhenThereAreStillMissingDemands(): void
    {
        // given
        $bankingSoft = new Project($this->demandForPhpMidInJan, new Decimal(9));
        $insuranceSoft = new Project($this->demandsForPhpAndJavaScriptInJan, new Decimal(99));
        $projects = new Projects(Map::fromArray([
            $this->bankingSoftId->toRfc4122() => $bankingSoft,
            $this->insuranceSoftId->toRfc4122() => $insuranceSoft,
        ]));

        // and
        $bankingSoft->add($this->staszekPhpMid);

        // when
        $result = $this->simulationFacade->checkPotentialTransfer($projects, $this->bankingSoftId, $this->insuranceSoftId, $this->staszekPhpMid, $this->jan1);

        // then
        self::assertTrue($result->equals(new Decimal(-9)));
    }
}
