<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Sorter;

final readonly class Edge implements \Stringable
{
    public function __construct(
        public int $source,
        public int $target
    ) {
    }

    public function __toString(): string
    {
        return sprintf('(%s -> %s)', $this->source, $this->target);
    }
}
