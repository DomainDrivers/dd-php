<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Optimization;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Optimization\Item;
use DomainDrivers\SmartSchedule\Optimization\OptimizationFacade;
use DomainDrivers\SmartSchedule\Optimization\TotalCapacity;
use DomainDrivers\SmartSchedule\Optimization\TotalWeight;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OptimizationFacade::class)]
final class OptimizationForTimedCapabilitiesTest extends TestCase
{
    private OptimizationFacade $optimizationFacade;

    protected function setUp(): void
    {
        $this->optimizationFacade = new OptimizationFacade();
    }

    #[Test]
    public function nothingIsChosenWhenNoCapacitiesInTimeSlot(): void
    {
        // given
        $june = TimeSlot::createMonthlyTimeSlotAtUTC(2020, 6);
        $october = TimeSlot::createMonthlyTimeSlotAtUTC(2020, 10);

        $items = GenericList::of(
            new Item('Item1', new Decimal(100), TotalWeight::of(new CapabilityTimedWeightDimension('COMMON SENSE', 'Skill', $june))),
            new Item('Item2', new Decimal(100), TotalWeight::of(new CapabilityTimedWeightDimension('THINKING', 'Skill', $june)))
        );

        // when
        $result = $this->optimizationFacade->calculate($items, TotalCapacity::of(
            new CapabilityTimedCapacityDimension('anna', 'COMMON SENSE', 'Skill', $october)
        ));

        // then
        self::assertTrue($result->profit->equals(new Decimal(0)));
        self::assertSame(0, $result->chosenItems->length());
    }

    #[Test]
    public function mostProfitableItemIsChosen(): void
    {
        // given
        $june = TimeSlot::createMonthlyTimeSlotAtUTC(2020, 6);

        $items = GenericList::of(
            new Item('Item1', new Decimal(200), TotalWeight::of(new CapabilityTimedWeightDimension('COMMON SENSE', 'Skill', $june))),
            new Item('Item2', new Decimal(100), TotalWeight::of(new CapabilityTimedWeightDimension('THINKING', 'Skill', $june)))
        );

        // when
        $result = $this->optimizationFacade->calculate($items, TotalCapacity::of(
            new CapabilityTimedCapacityDimension('anna', 'COMMON SENSE', 'Skill', $june)
        ));

        // then
        self::assertTrue($result->profit->equals(new Decimal(200)));
        self::assertSame(1, $result->chosenItems->length());
    }
}
