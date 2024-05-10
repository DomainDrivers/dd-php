<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Allocation\Cashflow;

use DomainDrivers\SmartSchedule\Allocation\Cashflow\Cost;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Earnings;
use DomainDrivers\SmartSchedule\Allocation\Cashflow\Income;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Earnings::class)]
final class EarningsTest extends TestCase
{
    #[Test]
    public function incomeMinusCostTest(): void
    {
        self::assertTrue(Earnings::of(9)->equals(Income::of(10)->minus(Cost::of(1))));
        self::assertTrue(Earnings::of(8)->equals(Income::of(10)->minus(Cost::of(2))));
        self::assertTrue(Earnings::of(7)->equals(Income::of(10)->minus(Cost::of(3))));
        self::assertTrue(Earnings::of(-70)->equals(Income::of(100)->minus(Cost::of(170))));
    }

    #[Test]
    public function greaterThanTest(): void
    {
        self::assertTrue(Earnings::of(10)->greaterThan(Earnings::of(9)));
        self::assertTrue(Earnings::of(10)->greaterThan(Earnings::of(0)));
        self::assertTrue(Earnings::of(10)->greaterThan(Earnings::of(-1)));
        self::assertFalse(Earnings::of(10)->greaterThan(Earnings::of(10)));
        self::assertFalse(Earnings::of(10)->greaterThan(Earnings::of(11)));
    }
}
