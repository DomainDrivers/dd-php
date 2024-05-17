<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use Munus\Collection\Map;
use Munus\Collection\Stream;
use Munus\Collection\Stream\Collectors;

final readonly class Calendars
{
    /**
     * @param Map<string, Calendar> $calendars
     */
    public function __construct(public Map $calendars)
    {
    }

    public static function of(Calendar ...$calendars): self
    {
        return new self(Stream::ofAll($calendars)->collect(Collectors::toMap(fn (Calendar $c): string => (string) $c->resourceId)));
    }

    public function get(ResourceId $resourceId): Calendar
    {
        return $this->calendars->get((string) $resourceId)->getOrElse(Calendar::empty($resourceId));
    }
}
