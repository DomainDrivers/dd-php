<?php

declare(strict_types=1);

namespace DomainDrivers\SmartSchedule\Availability;

use DomainDrivers\SmartSchedule\Availability\Segment\SegmentInMinutes;
use DomainDrivers\SmartSchedule\Availability\Segment\Segments;
use DomainDrivers\SmartSchedule\Shared\EventsPublisher;
use DomainDrivers\SmartSchedule\Shared\TimeSlot\TimeSlot;
use Munus\Collection\Set;
use Munus\Control\Option;
use Symfony\Component\Clock\ClockInterface;

class AvailabilityFacade
{
    public function __construct(
        private ResourceAvailabilityRepository $availabilityRepository,
        private ResourceAvailabilityReadModel $availabilityReadModel,
        private EventsPublisher $eventsPublisher,
        private ClockInterface $clock
    ) {
    }

    public function createResourceSlots(ResourceId $resourceId, TimeSlot $timeSlot): void
    {
        $this->availabilityRepository->saveGroup(ResourceGroupedAvailability::of($resourceId, $timeSlot));
    }

    public function createResourceSlotsWitParent(ResourceId $resourceId, ResourceId $parentId, TimeSlot $timeSlot): void
    {
        $this->availabilityRepository->saveGroup(ResourceGroupedAvailability::withParent($resourceId, $timeSlot, $parentId));
    }

    public function block(ResourceId $resourceId, TimeSlot $timeSlot, Owner $requester): bool
    {
        $toBlock = $this->findGrouped($resourceId, $timeSlot);

        return $this->doBlock($requester, $toBlock);
    }

    private function doBlock(Owner $requester, ResourceGroupedAvailability $toBlock): bool
    {
        if ($toBlock->hasNoSlots()) {
            return false;
        }
        if ($toBlock->block($requester)) {
            return $this->availabilityRepository->saveCheckingVersions($toBlock);
        }

        return false;
    }

    /**
     * @param Set<ResourceId> $resourceIds
     *
     * @return Option<ResourceId>
     */
    public function blockRandomAvailable(Set $resourceIds, TimeSlot $within, Owner $owner): Option
    {
        $normalized = Segments::normalizeToSegmentBoundaries($within, SegmentInMinutes::defaultSegment());
        $groupedAvailability = $this->availabilityRepository->loadAvailabilitiesOfRandomResourceWithin($resourceIds, $normalized);
        if ($this->doBlock($owner, $groupedAvailability)) {
            return $groupedAvailability->resourceId();
        }
        /** @var Option<ResourceId> $empty */
        $empty = Option::none();

        return $empty;
    }

    public function release(ResourceId $resourceId, TimeSlot $timeSlot, Owner $requester): bool
    {
        $toRelease = $this->findGrouped($resourceId, $timeSlot);
        if ($toRelease->hasNoSlots()) {
            return false;
        }
        if ($toRelease->release($requester)) {
            return $this->availabilityRepository->saveCheckingVersions($toRelease);
        }

        return false;
    }

    public function disable(ResourceId $resourceId, TimeSlot $timeSlot, Owner $requester): bool
    {
        $toDisable = $this->findGrouped($resourceId, $timeSlot);
        if ($toDisable->hasNoSlots()) {
            return false;
        }
        $previousOwners = $toDisable->owners();
        if ($toDisable->disable($requester)) {
            $result = $this->availabilityRepository->saveCheckingVersions($toDisable);
            if ($result) {
                $this->eventsPublisher->publish(ResourceTakenOver::new($resourceId, $previousOwners, $timeSlot, $this->clock->now()));
            }

            return $result;
        }

        return false;
    }

    public function find(ResourceId $resourceId, TimeSlot $within): ResourceGroupedAvailability
    {
        return new ResourceGroupedAvailability(
            $this->availabilityRepository->loadAllWithinSlot(
                $resourceId,
                Segments::normalizeToSegmentBoundaries($within, SegmentInMinutes::defaultSegment()
                ))
        );
    }

    public function findByParentId(ResourceId $parentId, TimeSlot $within): ResourceGroupedAvailability
    {
        return new ResourceGroupedAvailability(
            $this->availabilityRepository->loadAllByParentIdWithinSlot(
                $parentId,
                Segments::normalizeToSegmentBoundaries($within, SegmentInMinutes::defaultSegment()
                ))
        );
    }

    public function loadCalendar(ResourceId $resourceId, TimeSlot $within): Calendar
    {
        $normalized = Segments::normalizeToSegmentBoundaries($within, SegmentInMinutes::defaultSegment());

        return $this->availabilityReadModel->load($resourceId, $normalized);
    }

    /**
     * @param Set<ResourceId> $resourceIds
     */
    public function loadCalendars(Set $resourceIds, TimeSlot $within): Calendars
    {
        $normalized = Segments::normalizeToSegmentBoundaries($within, SegmentInMinutes::defaultSegment());

        return $this->availabilityReadModel->loadAll($resourceIds, $normalized);
    }

    private function findGrouped(ResourceId $resourceId, TimeSlot $within): ResourceGroupedAvailability
    {
        return new ResourceGroupedAvailability(
            $this->availabilityRepository->loadAllWithinSlot(
                $resourceId,
                Segments::normalizeToSegmentBoundaries($within, SegmentInMinutes::defaultSegment()
                ))
        );
    }
}
