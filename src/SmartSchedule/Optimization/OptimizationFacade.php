<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Optimization;

use Decimal\Decimal;
use Munus\Collection\GenericList;
use Munus\Collection\Map;

final readonly class OptimizationFacade
{
    /**
     * @param GenericList<Item> $items
     */
    public function calculate(GenericList $items, TotalCapacity $totalCapacity): Result
    {
        $allCapacities = $totalCapacity->components;
        $capacitiesSize = $totalCapacity->size();
        /** @var Decimal[] $dp */
        $dp = [];
        /** @var GenericList<Item>[] $chosenItemsList */
        $chosenItemsList = [];
        /** @var GenericList<CapacityDimension>[] $allocatedCapacitiesList */
        $allocatedCapacitiesList = [];
        /** @var GenericList<CapacityDimension> $emptyAllocation */
        $emptyAllocation = GenericList::empty();
        /** @var GenericList<Item> $emptyItems */
        $emptyItems = GenericList::empty();
        $itemToCapacitiesMap = Map::empty();

        $automaticallyIncludedItems = $items->filter(fn (Item $i) => $i->isWeightZero());
        $guaranteedValue = $automaticallyIncludedItems
            ->map(fn (Item $p) => $p->value)
            ->fold(new Decimal(0), fn (Decimal $sum, Decimal $e) => $sum->add($e));

        $itemsArray = $items->toArray();
        uasort($itemsArray, fn (Item $a, Item $b) => $a->value <=> $b->value);
        foreach (array_reverse($itemsArray) as $item) {
            $chosenCapacities = $this->matchCapacities($item->totalWeight, $allCapacities);
            $allCapacities = $allCapacities->filter(fn (CapacityDimension $c) => !$chosenCapacities->contains($c));

            if ($chosenCapacities->isEmpty()) {
                continue;
            }

            $sumValue = $item->value;
            $chosenCapacitiesCount = $chosenCapacities->length();

            for ($j = $capacitiesSize; $j >= $chosenCapacitiesCount; --$j) {
                if (($dp[$j] ?? new Decimal(0)) < $sumValue->add($dp[$j - $chosenCapacitiesCount] ?? new Decimal(0))) {
                    $dp[$j] = $sumValue->add($dp[$j - $chosenCapacitiesCount] ?? new Decimal(0));

                    $chosenItemsList[$j] = GenericList::ofAll($chosenItemsList[$j - $chosenCapacitiesCount] ?? [])
                        ->append($item);

                    $allocatedCapacitiesList[$j] = ($allocatedCapacitiesList[$j] ?? $emptyAllocation)->appendAll($chosenCapacities);
                }
            }
            $itemToCapacitiesMap = $itemToCapacitiesMap->put($item->name, $chosenCapacities);
        }

        /** @var GenericList<Item> $chosenProjects */
        $chosenProjects = ($chosenItemsList[$capacitiesSize] ?? $emptyItems)->appendAll($automaticallyIncludedItems);

        return new Result(
            ($dp[$capacitiesSize] ?? new Decimal(0))->add($guaranteedValue),
            $chosenProjects,
            $itemToCapacitiesMap
        );
    }

    /**
     * @param GenericList<covariant CapacityDimension> $availableCapacities
     *
     * @return GenericList<CapacityDimension>
     */
    private function matchCapacities(TotalWeight $totalWeight, GenericList $availableCapacities): GenericList
    {
        $result = GenericList::empty();
        foreach ($totalWeight->components->toArray() as $weightDimension) {
            $matchingCapacity = $availableCapacities
                ->filter(fn (CapacityDimension $d) => $weightDimension->isSatisfiedBy($d))
                ->getOrNull();
            if ($matchingCapacity !== null) {
                $result = $result->append($matchingCapacity);
            } else {
                return GenericList::empty();
            }
        }

        return $result;
    }
}
