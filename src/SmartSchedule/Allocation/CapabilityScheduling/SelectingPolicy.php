<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Allocation\CapabilityScheduling;

enum SelectingPolicy: string
{
    case ALL_SIMULTANEOUSLY = 'all_simultaneously';
    case ONE_OF_ALL = 'one_of_all';
}
