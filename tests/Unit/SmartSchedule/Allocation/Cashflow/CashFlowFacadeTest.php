<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation\Cashflow;

use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashFlowFacade;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Cost;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Earnings;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\EarningsRecalculated;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Income;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\NativeClock;

#[CoversClass(CashFlowFacade::class)]
final class CashFlowFacadeTest extends TestCase
{
    private CashFlowFacade $cashFlowFacade;
    private EventsPublisher&MockObject $eventsPublisher;

    protected function setUp(): void
    {
        $this->cashFlowFacade = new CashFlowFacade(
            new InMemoryCashflowRepository(),
            $this->eventsPublisher = $this->createMock(EventsPublisher::class),
            new NativeClock()
        );
    }

    #[Test]
    public function canSaveCashFlow(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();

        // when
        $this->cashFlowFacade->addIncomeAndCost($projectId, Income::of(100), Cost::of(50));

        // then
        self::assertTrue($this->cashFlowFacade->find($projectId)->equals(Earnings::of(50)));
    }

    #[Test]
    public function updatingCashFlowEmitsAnEvent(): void
    {
        // given
        $projectId = ProjectAllocationsId::newOne();
        $income = Income::of(100);
        $cost = Cost::of(50);

        // then
        $this->eventsPublisher->expects(self::once())->method('publish')->with(self::callback(fn (EarningsRecalculated $event): bool => $event->earnings->equals(Earnings::of(50))));

        // when
        $this->cashFlowFacade->addIncomeAndCost($projectId, $income, $cost);
    }
}
