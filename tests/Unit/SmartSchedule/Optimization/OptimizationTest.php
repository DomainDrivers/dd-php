<?php

declare(strict_types=1);

namespace DomainDrivers\Tests\Unit\SmartSchedule\Optimization;

use Decimal\Decimal;
use DomainDrivers\SmartSchedule\Optimization\Item;
use DomainDrivers\SmartSchedule\Optimization\OptimizationFacade;
use DomainDrivers\SmartSchedule\Optimization\TotalCapacity;
use DomainDrivers\SmartSchedule\Optimization\TotalWeight;
use Munus\Collection\GenericList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(OptimizationFacade::class)]
final class OptimizationTest extends TestCase
{
    private OptimizationFacade $optimizationFacade;

    #[\Override]
    protected function setUp(): void
    {
        $this->optimizationFacade = new OptimizationFacade();
    }

    #[Test]
    public function nothingIsChosenWhenNoCapacities(): void
    {
        // given
        $items = GenericList::of(
            new Item('Item1', new Decimal(100), TotalWeight::of(new CapabilityWeightDimension('COMMON SENSE', 'Skill'))),
            new Item('Item2', new Decimal(100), TotalWeight::of(new CapabilityWeightDimension('THINKING', 'Skill')))
        );

        // when
        $result = $this->optimizationFacade->calculate($items, TotalCapacity::zero());

        // then
        self::assertTrue($result->profit->equals(new Decimal(0)));
        self::assertSame(0, $result->chosenItems->length());
    }

    #[Test]
    public function everythingIsChosenWhenAllWeightsAreZero(): void
    {
        // given
        $items = GenericList::of(
            new Item('Item1', new Decimal(200), TotalWeight::zero()),
            new Item('Item2', new Decimal(100), TotalWeight::zero())
        );

        // when
        $result = $this->optimizationFacade->calculate($items, TotalCapacity::zero());

        // then
        self::assertTrue($result->profit->equals(new Decimal(300)));
        self::assertSame(2, $result->chosenItems->length());
    }

    #[Test]
    public function ifEnoughCapacityAllItemsAreChosen(): void
    {
        // given
        $items = GenericList::of(
            new Item('Item1', new Decimal(100), TotalWeight::of(new CapabilityWeightDimension('WEB DEVELOPMENT', 'Skill'))),
            new Item('Item2', new Decimal(300), TotalWeight::of(new CapabilityWeightDimension('WEB DEVELOPMENT', 'Skill')))
        );
        $c1 = new CapabilityCapacityDimension('Anna', 'WEB DEVELOPMENT', 'Skill');
        $c2 = new CapabilityCapacityDimension('Zbyniu', 'WEB DEVELOPMENT', 'Skill');

        // when
        $result = $this->optimizationFacade->calculate($items, TotalCapacity::of($c1, $c2));

        // then
        self::assertTrue($result->profit->equals(new Decimal(400)));
        self::assertSame(2, $result->chosenItems->length());
    }

    #[Test]
    public function mostValuableItemsAreChosen(): void
    {
        // given
        $item1 = new Item('Item1', new Decimal(100), TotalWeight::of(new CapabilityWeightDimension('PHP', 'Skill')));
        $item2 = new Item('Item2', new Decimal(500), TotalWeight::of(new CapabilityWeightDimension('PHP', 'Skill')));
        $item3 = new Item('Item3', new Decimal(300), TotalWeight::of(new CapabilityWeightDimension('PHP', 'Skill')));
        $totalCapacity = TotalCapacity::of(
            new CapabilityCapacityDimension('Anna', 'PHP', 'Skill'),
            new CapabilityCapacityDimension('Zbyniu', 'PHP', 'Skill')
        );

        // when
        $result = $this->optimizationFacade->calculate(GenericList::of($item1, $item2, $item3), $totalCapacity);

        // then
        self::assertTrue($result->profit->equals(new Decimal(800)));
        self::assertSame(2, $result->chosenItems->length());
        self::assertSame(1, $result->itemToCapacities->get($item3->name)->get()->length());
        self::assertTrue($totalCapacity->components->containsAll($result->itemToCapacities->get($item3->name)->get()));
        self::assertSame(1, $result->itemToCapacities->get($item2->name)->get()->length());
        self::assertTrue($totalCapacity->components->containsAll($result->itemToCapacities->get($item2->name)->get()));
        self::assertTrue($result->itemToCapacities->get($item1->name)->isEmpty());
    }
}
