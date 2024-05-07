<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning;

use Munus\Collection\Map;

final readonly class DemandsPerStage
{
    /**
     * @param Map<string, Demands> $demands
     */
    public function __construct(public Map $demands)
    {
    }

    public static function empty(): self
    {
        return new self(Map::empty());
    }
}
