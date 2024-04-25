<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Planning\Schedule;

use DomainDrivers\SmartSchedule\Planning\Parallelization\ResourceName;
use Munus\Collection\Map;
use Munus\Collection\Stream;
use Munus\Collection\Stream\Collectors;

/**
 * this class will be part of another module - possibly "Availability".
 */
final readonly class Calendars
{
    /**
     * @param Map<string, Calendar> $calendars
     */
    public function __construct(private Map $calendars)
    {
    }

    public static function of(Calendar ...$calendars): self
    {
        return new self(Stream::ofAll($calendars)->collect(Collectors::toMap(fn (Calendar $c) => $c->resourceId->name)));
    }

    public function get(ResourceName $resourceId): Calendar
    {
        return $this->calendars->get($resourceId->name)->getOrElse(Calendar::empty($resourceId));
    }
}
