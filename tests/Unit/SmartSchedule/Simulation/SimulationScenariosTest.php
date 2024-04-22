<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Simulation;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Simulation\AvailableResourceCapability;
use DomainDrivers\SmartSchedule\Simulation\Capability;
use DomainDrivers\SmartSchedule\Simulation\Demand;
use DomainDrivers\SmartSchedule\Simulation\ProjectId;
use DomainDrivers\SmartSchedule\Simulation\SimulationFacade;
use DomainDrivers\SmartSchedule\Simulation\TimeSlot;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

#[CoversClass(SimulationFacade::class)]
final class SimulationScenariosTest extends TestCase
{
    private TimeSlot $jan_1;
    private ProjectId $project_1;
    private ProjectId $project_2;
    private ProjectId $project_3;
    private Uuid $staszek;
    private Uuid $leon;
    private SimulationFacade $simulationFacade;

    protected function setUp(): void
    {
        $this->jan_1 = TimeSlot::createDailyTimeSlotAtUTC(2021, 1, 1);
        $this->project_1 = ProjectId::newOne();
        $this->project_2 = ProjectId::newOne();
        $this->project_3 = ProjectId::newOne();
        $this->staszek = Uuid::v7();
        $this->leon = Uuid::v7();
        $this->simulationFacade = new SimulationFacade();
    }

    #[Test]
    public function picksOptimalProjectBasedOnEarnings(): void
    {
        // given
        $simulatedProjects = $this->simulatedProjects()
            ->withProject($this->project_1)
            ->thatRequires(Demand::for(Capability::skill('JAVA-MID'), $this->jan_1))
            ->thatCanEarn(new Decimal(9))
            ->withProject($this->project_2)
            ->thatRequires(Demand::for(Capability::skill('JAVA-MID'), $this->jan_1))
            ->thatCanEarn(new Decimal(99))
            ->withProject($this->project_3)
            ->thatRequires(Demand::for(Capability::skill('JAVA-MID'), $this->jan_1))
            ->thatCanEarn(new Decimal(2))
            ->build();

        // and there are
        $simulatedAvailability = $this->simulatedCapabilities()
            ->withEmployee($this->staszek)
            ->thatBrings(Capability::skill('JAVA-MID'))
            ->thatIsAvailableAt($this->jan_1)
            ->withEmployee($this->leon)
            ->thatBrings(Capability::skill('JAVA-MID'))
            ->thatIsAvailableAt($this->jan_1)
            ->build();

        // when
        $result = $this->simulationFacade->whichProjectWithMissingDemandsIsMostProfitableToAllocateResourcesTo($simulatedProjects, $simulatedAvailability);

        // then
        self::assertTrue($result->profit->equals(new Decimal(108)));
        self::assertSame(2, $result->chosenProjects->length());
    }

    #[Test]
    public function picksAllWhenEnoughCapabilities(): void
    {
        // given
        $simulatedProjects = $this->simulatedProjects()
            ->withProject($this->project_1)
            ->thatRequires(Demand::for(Capability::skill('JAVA-MID'), $this->jan_1))
            ->thatCanEarn(new Decimal(99))
            ->build();

        // and there are
        $simulatedAvailability = $this->simulatedCapabilities()
            ->withEmployee($this->staszek)
            ->thatBrings(Capability::skill('JAVA-MID'))
            ->thatIsAvailableAt($this->jan_1)
            ->withEmployee($this->leon)
            ->thatBrings(Capability::skill('JAVA-MID'))
            ->thatIsAvailableAt($this->jan_1)
            ->build();

        // when
        $result = $this->simulationFacade->whichProjectWithMissingDemandsIsMostProfitableToAllocateResourcesTo($simulatedProjects, $simulatedAvailability);

        // then
        self::assertTrue($result->profit->equals(new Decimal(99)));
        self::assertSame(1, $result->chosenProjects->length());
    }

    #[Test]
    public function canSimulateHavingExtraResources(): void
    {
        // given
        $simulatedProjects = $this->simulatedProjects()
            ->withProject($this->project_1)
            ->thatRequires(Demand::for(Capability::skill('YT DRAMA COMMENTS'), $this->jan_1))
            ->thatCanEarn(new Decimal(9))
            ->withProject($this->project_2)
            ->thatRequires(Demand::for(Capability::skill('YT DRAMA COMMENTS'), $this->jan_1))
            ->thatCanEarn(new Decimal(99))
            ->build();

        // and there are
        $simulatedAvailability = $this->simulatedCapabilities()
            ->withEmployee($this->staszek)
            ->thatBrings(Capability::skill('YT DRAMA COMMENTS'))
            ->thatIsAvailableAt($this->jan_1)
            ->build();

        // and there are
        $extraCapability = new AvailableResourceCapability(Uuid::v7(), Capability::skill('YT DRAMA COMMENTS'), $this->jan_1);

        // when
        $resultWithoutExtraResource = $this->simulationFacade->whichProjectWithMissingDemandsIsMostProfitableToAllocateResourcesTo($simulatedProjects, $simulatedAvailability);
        $resultWithExtraResource = $this->simulationFacade->whichProjectWithMissingDemandsIsMostProfitableToAllocateResourcesTo(
            $simulatedProjects,
            $simulatedAvailability->add($extraCapability)
        );

        // then
        self::assertTrue($resultWithoutExtraResource->profit->equals(new Decimal(99)));
        self::assertTrue($resultWithExtraResource->profit->equals(new Decimal(108)));
    }

    private function simulatedProjects(): SimulatedProjectsBuilder
    {
        return new SimulatedProjectsBuilder();
    }

    private function simulatedCapabilities(): AvailableCapabilitiesBuilder
    {
        return new AvailableCapabilitiesBuilder();
    }
}
