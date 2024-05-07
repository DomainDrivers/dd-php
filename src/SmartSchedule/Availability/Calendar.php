<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Shared\ResourceName;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\GenericList;
use Munus\Collection\Map;

final readonly class Calendar
{
    /**
     * @param Map<string, GenericList<TimeSlot>> $calendar
     */
    public function __construct(public ResourceName $resourceId, public Map $calendar)
    {
    }

    public static function empty(ResourceName $resourceId): self
    {
        return new self($resourceId, Map::empty());
    }

    public static function withAvailableSlots(ResourceName $resourceId, TimeSlot ...$availableSlots): self
    {
        return new self($resourceId, Map::fromArray(['' => GenericList::ofAll($availableSlots)]));
    }

    /**
     * @return GenericList<TimeSlot>
     */
    public function availableSlots(): GenericList
    {
        $slots = $this->calendar->get('')->getOrElse(GenericList::empty())->toArray();
        uasort($slots, fn (TimeSlot $a, TimeSlot $b) => $a->from->getTimestamp() <=> $b->from->getTimestamp());

        return GenericList::ofAll($slots);
    }
}
