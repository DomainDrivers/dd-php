<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation\Cashflow;

use DomainDrivers\SmartSchedule\Allocation\Cashflow\CashFlowFacade;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Cost;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Earnings;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Income;
use DomainDrivers\SmartSchedule\Allocation\ProjectAllocationsId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(CashFlowFacade::class)]
final class CashFlowFacadeTest extends KernelTestCase
{
    private CashFlowFacade $cashFlowFacade;

    protected function setUp(): void
    {
        $this->cashFlowFacade = self::getContainer()->get(CashFlowFacade::class);
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
}
