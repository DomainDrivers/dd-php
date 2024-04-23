<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Optimization;

use Decimal\Decimal;
use Munus\Collection\GenericList;
use Munus\Collection\Map;
use Munus\Collection\Set;

final readonly class Result
{
    /**
     * @param GenericList<Item>                   $chosenItems
     * @param Map<string, Set<CapacityDimension>> $itemToCapacities
     */
    public function __construct(
        public Decimal $profit,
        public GenericList $chosenItems,
        public Map $itemToCapacities
    ) {
    }
}
