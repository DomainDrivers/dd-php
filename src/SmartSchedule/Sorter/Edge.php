<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

final readonly class Edge
{
    public function __construct(
        public int $source,
        public int $target
    ) {
    }
}
