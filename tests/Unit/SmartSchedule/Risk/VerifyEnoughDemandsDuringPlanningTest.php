<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Risk;

use DomainDrivers\SmartSchedule\Planning\Demand;
use DomainDrivers\SmartSchedule\Planning\Demands;
use DomainDrivers\SmartSchedule\Planning\PlanningFacade;
use DomainDrivers\SmartSchedule\Resource\Employee\EmployeeFacade;
use DomainDrivers\SmartSchedule\Resource\Employee\Seniority;
use DomainDrivers\SmartSchedule\Risk\RiskPushNotification;
use DomainDrivers\SmartSchedule\Risk\VerifyEnoughDemandsDuringPlanning;
use DomainDrivers\SmartSchedule\Shared\Capability\Capability;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

#[CoversClass(VerifyEnoughDemandsDuringPlanning::class)]
final class VerifyEnoughDemandsDuringPlanningTest extends KernelTestCase
{
    use InteractsWithMessenger;

    private RiskPushNotification&MockObject $riskPushNotification;
    private EmployeeFacade $employeeFacade;
    private PlanningFacade $planningFacade;

    protected function setUp(): void
    {
        $this->riskPushNotification = $this->createMock(RiskPushNotification::class);
        self::getContainer()->set(RiskPushNotification::class, $this->riskPushNotification);
        $this->employeeFacade = self::getContainer()->get(EmployeeFacade::class);
        $this->planningFacade = self::getContainer()->get(PlanningFacade::class);
    }

    #[Test]
    public function doesNothingWhenEnoughResources(): void
    {
        // given
        $this->employeeFacade->addEmployee('resourceName', 'lastName', Seniority::SENIOR, Capability::skills('php', 'python'), Capability::permissions());
        $this->employeeFacade->addEmployee('resourceName', 'lastName', Seniority::SENIOR, Capability::skills('c#', 'rust'), Capability::permissions());
        // and
        $projectId = $this->planningFacade->addNewProjectWith('php9');

        // when
        $this->planningFacade->addDemands($projectId, Demands::of(Demand::forSkill('php')));

        // then
        $this->riskPushNotification->expects(self::never())->method('notifyAboutPossibleRiskDuringPlanning');
        $this->transport('event')->process(1);
    }

    #[Test]
    public function notifiesWhenNotEnoughResources(): void
    {
        // given
        $this->employeeFacade->addEmployee('resourceName', 'lastName', Seniority::SENIOR, Capability::skills('php'), Capability::permissions());
        $this->employeeFacade->addEmployee('resourceName', 'lastName', Seniority::SENIOR, Capability::skills('c'), Capability::permissions());
        // and
        $projectPhp = $this->planningFacade->addNewProjectWith('php');
        $projectC = $this->planningFacade->addNewProjectWith('c');
        // and
        $this->planningFacade->addDemands($projectPhp, Demands::of(Demand::forSkill('php')));
        $this->planningFacade->addDemands($projectC, Demands::of(Demand::forSkill('c')));
        // when
        $rust = $this->planningFacade->addNewProjectWith('rust');
        $this->planningFacade->addDemands($rust, Demands::of(Demand::forSkill('rust')));

        // then
        $this->riskPushNotification->expects(self::atLeastOnce())->method('notifyAboutPossibleRiskDuringPlanning')->with(
            $rust,
            Demands::of(Demand::forSkill('rust'))
        );
        $this->transport('event')->process(3);
    }
}
